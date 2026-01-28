"""Modèles de base de données."""
from datetime import datetime
from enum import Enum as PyEnum
from sqlalchemy import (
    Column,
    Integer,
    String,
    Text,
    DateTime,
    Boolean,
    Enum,
    ForeignKey,
    Float,
)
from sqlalchemy.orm import relationship

from app.database import Base


class PlanType(str, PyEnum):
    """Types de plans d'abonnement."""

    FREE = "free"
    PRO = "pro"


class TypeBien(str, PyEnum):
    """Types de biens immobiliers."""

    APPARTEMENT = "appartement"
    MAISON = "maison"
    TERRAIN = "terrain"


class TypeTransaction(str, PyEnum):
    """Types de transactions."""

    VENTE = "vente"
    LOCATION = "location"


class Gamme(str, PyEnum):
    """Gammes de biens."""

    STANDARD = "standard"
    HAUT_DE_GAMME = "haut_de_gamme"


class TonRedaction(str, PyEnum):
    """Tons de rédaction."""

    PROFESSIONNEL = "professionnel"
    COURT = "court"
    PREMIUM = "premium"


class User(Base):
    """Modèle utilisateur."""

    __tablename__ = "users"

    id = Column(Integer, primary_key=True, index=True)
    email = Column(String(255), unique=True, index=True, nullable=False)
    hashed_password = Column(String(255), nullable=False)
    is_active = Column(Boolean, default=True)
    is_verified = Column(Boolean, default=False)
    plan = Column(Enum(PlanType), default=PlanType.FREE)
    stripe_customer_id = Column(String(255), nullable=True)
    stripe_subscription_id = Column(String(255), nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relations
    annonces = relationship("Annonce", back_populates="user")
    usage = relationship("UsageMonthly", back_populates="user")


class Annonce(Base):
    """Modèle pour les annonces générées."""

    __tablename__ = "annonces"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False)

    # Texte original et généré
    texte_original = Column(Text, nullable=False)
    texte_professionnel = Column(Text, nullable=True)
    texte_court = Column(Text, nullable=True)
    texte_premium = Column(Text, nullable=True)

    # Paramètres
    type_bien = Column(Enum(TypeBien), nullable=False)
    type_transaction = Column(Enum(TypeTransaction), nullable=False)
    gamme = Column(Enum(Gamme), nullable=False)
    ton = Column(Enum(TonRedaction), nullable=True)

    # Scores
    score_clarte = Column(Float, nullable=True)
    score_attractivite = Column(Float, nullable=True)
    score_lisibilite = Column(Float, nullable=True)
    score_global = Column(Float, nullable=True)

    # Métadonnées
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relations
    user = relationship("User", back_populates="annonces")


class UsageMonthly(Base):
    """Suivi de l'utilisation mensuelle."""

    __tablename__ = "usage_monthly"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False)
    year = Column(Integer, nullable=False)
    month = Column(Integer, nullable=False)
    count = Column(Integer, default=0)

    # Relations
    user = relationship("User", back_populates="usage")
