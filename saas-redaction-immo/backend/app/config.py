"""Configuration de l'application."""
import os
from functools import lru_cache
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    """Paramètres de configuration."""

    # Base de données MySQL
    database_url: str = "mysql+pymysql://u482325361_immo:Immo006*cannes@localhost:3306/u482325361_immo"

    # API IA
    anthropic_api_key: str = ""
    openai_api_key: str = ""

    # Stripe
    stripe_secret_key: str = ""
    stripe_publishable_key: str = ""
    stripe_webhook_secret: str = ""
    stripe_price_pro_monthly: str = ""

    # JWT
    jwt_secret_key: str = "change-me-in-production"
    jwt_algorithm: str = "HS256"
    access_token_expire_minutes: int = 30

    # Application
    app_env: str = "development"
    frontend_url: str = "http://localhost:3000"

    # Limites
    free_plan_limit: int = 3

    class Config:
        env_file = ".env"


@lru_cache()
def get_settings() -> Settings:
    """Récupère les paramètres (avec cache)."""
    return Settings()
