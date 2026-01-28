"""Routes pour les annonces et la réécriture."""
from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session

from app.config import get_settings
from app.database import get_db
from app.models import User, Annonce, UsageMonthly, PlanType, TonRedaction
from app.schemas import (
    RewriteRequest,
    RewriteResponse,
    ScoreQualite,
    AnnonceList,
    AnnonceListItem,
    UsageResponse,
)
from app.services.auth import get_current_user
from app.services.rewriter import rewrite_with_claude, rewrite_all_versions
from app.services.scoring import calculer_scores

settings = get_settings()
router = APIRouter(prefix="/annonces", tags=["Annonces"])


def check_usage_limit(user: User, db: Session) -> bool:
    """Vérifie si l'utilisateur a dépassé sa limite."""
    if user.plan == PlanType.PRO:
        return True

    now = datetime.utcnow()
    usage = (
        db.query(UsageMonthly)
        .filter(
            UsageMonthly.user_id == user.id,
            UsageMonthly.year == now.year,
            UsageMonthly.month == now.month,
        )
        .first()
    )

    if not usage:
        return True

    return usage.count < settings.free_plan_limit


def increment_usage(user: User, db: Session):
    """Incrémente le compteur d'utilisation."""
    now = datetime.utcnow()
    usage = (
        db.query(UsageMonthly)
        .filter(
            UsageMonthly.user_id == user.id,
            UsageMonthly.year == now.year,
            UsageMonthly.month == now.month,
        )
        .first()
    )

    if usage:
        usage.count += 1
    else:
        usage = UsageMonthly(
            user_id=user.id,
            year=now.year,
            month=now.month,
            count=1,
        )
        db.add(usage)

    db.commit()


@router.get("/usage", response_model=UsageResponse)
async def get_usage(
    current_user: User = Depends(get_current_user), db: Session = Depends(get_db)
):
    """Récupère l'utilisation de l'utilisateur."""
    now = datetime.utcnow()
    usage = (
        db.query(UsageMonthly)
        .filter(
            UsageMonthly.user_id == current_user.id,
            UsageMonthly.year == now.year,
            UsageMonthly.month == now.month,
        )
        .first()
    )

    used = usage.count if usage else 0
    limit = -1 if current_user.plan == PlanType.PRO else settings.free_plan_limit
    remaining = -1 if limit == -1 else max(0, limit - used)

    return UsageResponse(
        used=used,
        limit=limit,
        remaining=remaining,
        plan=current_user.plan,
    )


@router.post("/rewrite", response_model=RewriteResponse)
async def rewrite_annonce(
    request: RewriteRequest,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Réécrit une annonce immobilière avec l'IA."""
    # Vérifier la limite
    if not check_usage_limit(current_user, db):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Limite mensuelle atteinte. Passez au plan Pro pour des générations illimitées.",
        )

    try:
        # Générer les trois versions
        texte_pro, texte_court, texte_premium = rewrite_all_versions(
            request.texte_original,
            request.type_bien,
            request.type_transaction,
            request.gamme,
        )

        # Calculer les scores sur la version professionnelle
        clarte, attractivite, lisibilite, global_score = calculer_scores(texte_pro)

        # Sauvegarder l'annonce
        annonce = Annonce(
            user_id=current_user.id,
            texte_original=request.texte_original,
            texte_professionnel=texte_pro,
            texte_court=texte_court,
            texte_premium=texte_premium,
            type_bien=request.type_bien,
            type_transaction=request.type_transaction,
            gamme=request.gamme,
            ton=request.ton,
            score_clarte=clarte,
            score_attractivite=attractivite,
            score_lisibilite=lisibilite,
            score_global=global_score,
        )
        db.add(annonce)
        db.commit()
        db.refresh(annonce)

        # Incrémenter l'utilisation
        increment_usage(current_user, db)

        return RewriteResponse(
            id=annonce.id,
            texte_original=annonce.texte_original,
            texte_professionnel=annonce.texte_professionnel,
            texte_court=annonce.texte_court,
            texte_premium=annonce.texte_premium,
            scores=ScoreQualite(
                clarte=clarte,
                attractivite=attractivite,
                lisibilite=lisibilite,
                global_score=global_score,
            ),
            type_bien=annonce.type_bien,
            type_transaction=annonce.type_transaction,
            gamme=annonce.gamme,
            created_at=annonce.created_at,
        )

    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Erreur lors de la génération : {str(e)}",
        )


@router.post("/rewrite-single", response_model=RewriteResponse)
async def rewrite_single_version(
    request: RewriteRequest,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Réécrit une annonce avec un ton spécifique."""
    # Vérifier la limite
    if not check_usage_limit(current_user, db):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Limite mensuelle atteinte. Passez au plan Pro pour des générations illimitées.",
        )

    try:
        # Générer une seule version
        texte_reecrit = rewrite_with_claude(
            request.texte_original,
            request.type_bien,
            request.type_transaction,
            request.gamme,
            request.ton,
        )

        # Calculer les scores
        clarte, attractivite, lisibilite, global_score = calculer_scores(texte_reecrit)

        # Sauvegarder l'annonce
        annonce = Annonce(
            user_id=current_user.id,
            texte_original=request.texte_original,
            type_bien=request.type_bien,
            type_transaction=request.type_transaction,
            gamme=request.gamme,
            ton=request.ton,
            score_clarte=clarte,
            score_attractivite=attractivite,
            score_lisibilite=lisibilite,
            score_global=global_score,
        )

        # Assigner au bon champ selon le ton
        if request.ton == TonRedaction.PROFESSIONNEL:
            annonce.texte_professionnel = texte_reecrit
        elif request.ton == TonRedaction.COURT:
            annonce.texte_court = texte_reecrit
        else:
            annonce.texte_premium = texte_reecrit

        db.add(annonce)
        db.commit()
        db.refresh(annonce)

        # Incrémenter l'utilisation
        increment_usage(current_user, db)

        return RewriteResponse(
            id=annonce.id,
            texte_original=annonce.texte_original,
            texte_professionnel=annonce.texte_professionnel,
            texte_court=annonce.texte_court,
            texte_premium=annonce.texte_premium,
            scores=ScoreQualite(
                clarte=clarte,
                attractivite=attractivite,
                lisibilite=lisibilite,
                global_score=global_score,
            ),
            type_bien=annonce.type_bien,
            type_transaction=annonce.type_transaction,
            gamme=annonce.gamme,
            created_at=annonce.created_at,
        )

    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Erreur lors de la génération : {str(e)}",
        )


@router.get("/history", response_model=AnnonceList)
async def get_history(
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=50),
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Récupère l'historique des annonces de l'utilisateur."""
    # Compter le total
    total = db.query(Annonce).filter(Annonce.user_id == current_user.id).count()

    # Récupérer les annonces paginées
    annonces = (
        db.query(Annonce)
        .filter(Annonce.user_id == current_user.id)
        .order_by(Annonce.created_at.desc())
        .offset((page - 1) * per_page)
        .limit(per_page)
        .all()
    )

    items = [
        AnnonceListItem(
            id=a.id,
            texte_original=a.texte_original[:200] + "..."
            if len(a.texte_original) > 200
            else a.texte_original,
            type_bien=a.type_bien,
            type_transaction=a.type_transaction,
            score_global=a.score_global,
            created_at=a.created_at,
        )
        for a in annonces
    ]

    return AnnonceList(items=items, total=total, page=page, per_page=per_page)


@router.get("/{annonce_id}", response_model=RewriteResponse)
async def get_annonce(
    annonce_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Récupère une annonce spécifique."""
    annonce = (
        db.query(Annonce)
        .filter(Annonce.id == annonce_id, Annonce.user_id == current_user.id)
        .first()
    )

    if not annonce:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Annonce non trouvée",
        )

    return RewriteResponse(
        id=annonce.id,
        texte_original=annonce.texte_original,
        texte_professionnel=annonce.texte_professionnel,
        texte_court=annonce.texte_court,
        texte_premium=annonce.texte_premium,
        scores=ScoreQualite(
            clarte=annonce.score_clarte or 0,
            attractivite=annonce.score_attractivite or 0,
            lisibilite=annonce.score_lisibilite or 0,
            global_score=annonce.score_global or 0,
        ),
        type_bien=annonce.type_bien,
        type_transaction=annonce.type_transaction,
        gamme=annonce.gamme,
        created_at=annonce.created_at,
    )


@router.delete("/{annonce_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_annonce(
    annonce_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Supprime une annonce."""
    annonce = (
        db.query(Annonce)
        .filter(Annonce.id == annonce_id, Annonce.user_id == current_user.id)
        .first()
    )

    if not annonce:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Annonce non trouvée",
        )

    db.delete(annonce)
    db.commit()
