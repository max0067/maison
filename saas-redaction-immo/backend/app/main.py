"""Application FastAPI principale."""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.config import get_settings
from app.database import engine, Base
from app.routers import auth, annonces, subscriptions

settings = get_settings()

# Créer les tables
Base.metadata.create_all(bind=engine)

# Créer l'application
app = FastAPI(
    title="SaaS Rédaction Immobilière",
    description="API pour la réécriture professionnelle d'annonces immobilières",
    version="1.0.0",
)

# Configurer CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=[settings.frontend_url, "http://localhost:3000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Inclure les routers
app.include_router(auth.router, prefix="/api")
app.include_router(annonces.router, prefix="/api")
app.include_router(subscriptions.router, prefix="/api")


@app.get("/")
async def root():
    """Route racine."""
    return {
        "message": "SaaS Rédaction Immobilière API",
        "version": "1.0.0",
        "docs": "/docs",
    }


@app.get("/health")
async def health_check():
    """Vérification de santé."""
    return {"status": "healthy"}
