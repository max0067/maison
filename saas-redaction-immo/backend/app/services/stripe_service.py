"""Service Stripe pour la gestion des abonnements."""
import stripe
from typing import Optional

from app.config import get_settings

settings = get_settings()
stripe.api_key = settings.stripe_secret_key


def create_customer(email: str, user_id: int) -> str:
    """Crée un client Stripe."""
    customer = stripe.Customer.create(
        email=email,
        metadata={"user_id": str(user_id)},
    )
    return customer.id


def create_checkout_session(
    customer_id: str, price_id: str, success_url: str, cancel_url: str
) -> dict:
    """Crée une session de paiement Stripe Checkout."""
    session = stripe.checkout.Session.create(
        customer=customer_id,
        payment_method_types=["card"],
        line_items=[
            {
                "price": price_id,
                "quantity": 1,
            }
        ],
        mode="subscription",
        success_url=success_url,
        cancel_url=cancel_url,
    )
    return {"checkout_url": session.url, "session_id": session.id}


def create_portal_session(customer_id: str, return_url: str) -> str:
    """Crée une session du portail client Stripe."""
    session = stripe.billing_portal.Session.create(
        customer=customer_id,
        return_url=return_url,
    )
    return session.url


def get_subscription(subscription_id: str) -> Optional[dict]:
    """Récupère les informations d'un abonnement."""
    try:
        subscription = stripe.Subscription.retrieve(subscription_id)
        return {
            "id": subscription.id,
            "status": subscription.status,
            "current_period_end": subscription.current_period_end,
            "cancel_at_period_end": subscription.cancel_at_period_end,
        }
    except stripe.error.StripeError:
        return None


def cancel_subscription(subscription_id: str) -> bool:
    """Annule un abonnement à la fin de la période."""
    try:
        stripe.Subscription.modify(
            subscription_id,
            cancel_at_period_end=True,
        )
        return True
    except stripe.error.StripeError:
        return False


def construct_webhook_event(payload: bytes, sig_header: str) -> dict:
    """Construit un événement webhook Stripe."""
    return stripe.Webhook.construct_event(
        payload, sig_header, settings.stripe_webhook_secret
    )
