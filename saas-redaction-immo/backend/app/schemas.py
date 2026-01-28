"""Schémas Pydantic pour l'API."""
from datetime import datetime
from typing import Optional, List
from pydantic import BaseModel, EmailStr, Field

from app.models import TypeBien, TypeTransaction, Gamme, TonRedaction, PlanType


# ============== AUTH ==============


class UserCreate(BaseModel):
    """Schéma pour créer un utilisateur."""

    email: EmailStr
    password: str = Field(..., min_length=8)


class UserLogin(BaseModel):
    """Schéma pour la connexion."""

    email: EmailStr
    password: str


class Token(BaseModel):
    """Schéma pour le token JWT."""

    access_token: str
    token_type: str = "bearer"


class UserResponse(BaseModel):
    """Schéma de réponse utilisateur."""

    id: int
    email: str
    plan: PlanType
    is_verified: bool
    created_at: datetime

    class Config:
        from_attributes = True


# ============== ANNONCES ==============


class RewriteRequest(BaseModel):
    """Schéma pour la requête de réécriture."""

    texte_original: str = Field(..., min_length=50, max_length=10000)
    type_bien: TypeBien
    type_transaction: TypeTransaction
    gamme: Gamme
    ton: TonRedaction = TonRedaction.PROFESSIONNEL


class ScoreQualite(BaseModel):
    """Scores de qualité de l'annonce."""

    clarte: float = Field(..., ge=0, le=100)
    attractivite: float = Field(..., ge=0, le=100)
    lisibilite: float = Field(..., ge=0, le=100)
    global_score: float = Field(..., ge=0, le=100)


class RewriteResponse(BaseModel):
    """Schéma de réponse pour la réécriture."""

    id: int
    texte_original: str
    texte_professionnel: Optional[str] = None
    texte_court: Optional[str] = None
    texte_premium: Optional[str] = None
    scores: ScoreQualite
    type_bien: TypeBien
    type_transaction: TypeTransaction
    gamme: Gamme
    created_at: datetime

    class Config:
        from_attributes = True


class AnnonceListItem(BaseModel):
    """Schéma pour la liste des annonces."""

    id: int
    texte_original: str
    type_bien: TypeBien
    type_transaction: TypeTransaction
    score_global: Optional[float]
    created_at: datetime

    class Config:
        from_attributes = True


class AnnonceList(BaseModel):
    """Schéma pour la liste paginée des annonces."""

    items: List[AnnonceListItem]
    total: int
    page: int
    per_page: int


# ============== ABONNEMENTS ==============


class UsageResponse(BaseModel):
    """Schéma pour l'utilisation."""

    used: int
    limit: int
    remaining: int
    plan: PlanType


class SubscriptionCreate(BaseModel):
    """Schéma pour créer un abonnement."""

    price_id: str


class SubscriptionResponse(BaseModel):
    """Schéma de réponse abonnement."""

    subscription_id: str
    status: str
    current_period_end: datetime


class CheckoutSessionResponse(BaseModel):
    """Schéma pour la session de paiement Stripe."""

    checkout_url: str
    session_id: str
