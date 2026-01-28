"""Routes pour les abonnements Stripe."""
from fastapi import APIRouter, Depends, HTTPException, status, Request
from sqlalchemy.orm import Session

from app.config import get_settings
from app.database import get_db
from app.models import User, PlanType
from app.schemas import CheckoutSessionResponse
from app.services.auth import get_current_user
from app.services.stripe_service import (
    create_checkout_session,
    create_portal_session,
    create_customer,
    get_subscription,
    cancel_subscription,
    construct_webhook_event,
)

settings = get_settings()
router = APIRouter(prefix="/subscriptions", tags=["Abonnements"])


@router.post("/create-checkout", response_model=CheckoutSessionResponse)
async def create_checkout(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Crée une session de paiement Stripe pour passer au plan Pro."""
    # Vérifier si l'utilisateur a déjà un abonnement Pro
    if current_user.plan == PlanType.PRO:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Vous avez déjà un abonnement Pro",
        )

    # Créer le client Stripe si nécessaire
    if not current_user.stripe_customer_id:
        customer_id = create_customer(current_user.email, current_user.id)
        current_user.stripe_customer_id = customer_id
        db.commit()

    # Créer la session de paiement
    success_url = f"{settings.frontend_url}/subscription/success?session_id={{CHECKOUT_SESSION_ID}}"
    cancel_url = f"{settings.frontend_url}/subscription/cancel"

    checkout_data = create_checkout_session(
        customer_id=current_user.stripe_customer_id,
        price_id=settings.stripe_price_pro_monthly,
        success_url=success_url,
        cancel_url=cancel_url,
    )

    return CheckoutSessionResponse(
        checkout_url=checkout_data["checkout_url"],
        session_id=checkout_data["session_id"],
    )


@router.post("/portal")
async def create_customer_portal(
    current_user: User = Depends(get_current_user),
):
    """Crée une session du portail client Stripe."""
    if not current_user.stripe_customer_id:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Aucun abonnement trouvé",
        )

    return_url = f"{settings.frontend_url}/account"
    portal_url = create_portal_session(current_user.stripe_customer_id, return_url)

    return {"portal_url": portal_url}


@router.get("/status")
async def get_subscription_status(
    current_user: User = Depends(get_current_user),
):
    """Récupère le statut de l'abonnement."""
    if not current_user.stripe_subscription_id:
        return {
            "plan": current_user.plan,
            "subscription": None,
        }

    subscription = get_subscription(current_user.stripe_subscription_id)

    return {
        "plan": current_user.plan,
        "subscription": subscription,
    }


@router.post("/cancel")
async def cancel_user_subscription(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Annule l'abonnement de l'utilisateur."""
    if not current_user.stripe_subscription_id:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Aucun abonnement à annuler",
        )

    success = cancel_subscription(current_user.stripe_subscription_id)

    if not success:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Erreur lors de l'annulation",
        )

    return {"message": "Abonnement annulé. Il restera actif jusqu'à la fin de la période."}


@router.post("/webhook")
async def stripe_webhook(request: Request, db: Session = Depends(get_db)):
    """Webhook Stripe pour gérer les événements."""
    payload = await request.body()
    sig_header = request.headers.get("stripe-signature")

    try:
        event = construct_webhook_event(payload, sig_header)
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))

    # Gérer les événements
    if event["type"] == "checkout.session.completed":
        session = event["data"]["object"]
        customer_id = session["customer"]
        subscription_id = session["subscription"]

        # Mettre à jour l'utilisateur
        user = (
            db.query(User).filter(User.stripe_customer_id == customer_id).first()
        )
        if user:
            user.plan = PlanType.PRO
            user.stripe_subscription_id = subscription_id
            db.commit()

    elif event["type"] == "customer.subscription.deleted":
        subscription = event["data"]["object"]
        subscription_id = subscription["id"]

        # Rétrograder l'utilisateur
        user = (
            db.query(User).filter(User.stripe_subscription_id == subscription_id).first()
        )
        if user:
            user.plan = PlanType.FREE
            user.stripe_subscription_id = None
            db.commit()

    elif event["type"] == "customer.subscription.updated":
        subscription = event["data"]["object"]
        subscription_id = subscription["id"]

        user = (
            db.query(User).filter(User.stripe_subscription_id == subscription_id).first()
        )
        if user:
            if subscription["status"] == "active":
                user.plan = PlanType.PRO
            elif subscription["status"] in ["canceled", "unpaid"]:
                user.plan = PlanType.FREE
            db.commit()

    return {"received": True}
