# SaaS Rédaction Immobilière - RedacImmo

Application SaaS permettant aux agents immobiliers de transformer leurs annonces brutes en textes professionnels optimisés grâce à l'IA.

## Fonctionnalités

### EPIC 1 - Interface utilisateur
- Page unique avec zone de texte pour coller l'annonce
- Sélecteurs : Type de bien, Transaction, Gamme
- 3 boutons de génération : Professionnel, Court, Premium
- Affichage du résultat avec comparaison Avant/Après
- Bouton Copier

### EPIC 2 - Moteur IA de rédaction
- Endpoint API `/api/annonces/rewrite`
- Utilisation de Claude (Anthropic) pour la réécriture
- 3 versions générées simultanément
- Prompt optimisé pour l'immobilier

### EPIC 3 - Score qualité
- Score de Clarté (longueur des phrases)
- Score d'Attractivité (vocabulaire riche, mots faibles)
- Score de Lisibilité (structure, ponctuation)
- Score Global /100 avec indicateur couleur

### EPIC 4 - Historique utilisateur
- Sauvegarde de toutes les annonces générées
- Pagination de l'historique
- Possibilité de revoir et supprimer

### EPIC 5 - Abonnements Stripe
- Plan Free : 3 annonces/mois
- Plan Pro : illimité (19€/mois)
- Intégration Stripe Checkout
- Portail client pour gérer l'abonnement

### EPIC 6 - Landing Page
- Présentation du service
- Tarifs
- Appels à l'action

## Architecture technique

```
saas-redaction-immo/
├── backend/                 # API FastAPI
│   ├── app/
│   │   ├── main.py         # Point d'entrée
│   │   ├── config.py       # Configuration
│   │   ├── database.py     # Connexion DB
│   │   ├── models.py       # Modèles SQLAlchemy
│   │   ├── schemas.py      # Schémas Pydantic
│   │   ├── routers/        # Routes API
│   │   │   ├── auth.py
│   │   │   ├── annonces.py
│   │   │   └── subscriptions.py
│   │   └── services/       # Logique métier
│   │       ├── auth.py
│   │       ├── rewriter.py
│   │       ├── scoring.py
│   │       └── stripe_service.py
│   └── requirements.txt
│
├── frontend/               # Application Next.js
│   ├── src/
│   │   ├── app/           # Pages (App Router)
│   │   ├── components/    # Composants React
│   │   ├── hooks/         # Hooks personnalisés
│   │   ├── lib/           # Utilitaires (API client)
│   │   └── types/         # Types TypeScript
│   └── package.json
│
└── database/
    └── setup.sql          # Script d'initialisation
```

## Installation

### Prérequis
- Python 3.10+
- Node.js 18+
- MySQL 5.7+

### Backend

```bash
cd backend

# Créer l'environnement virtuel
python -m venv venv
source venv/bin/activate  # Linux/Mac
# ou: venv\Scripts\activate  # Windows

# Installer les dépendances
pip install -r requirements.txt

# Configurer les variables d'environnement
cp .env.example .env
# Éditer .env avec vos clés API

# Lancer le serveur
uvicorn app.main:app --reload --port 8000
```

### Frontend

```bash
cd frontend

# Installer les dépendances
npm install

# Configurer les variables d'environnement
cp .env.local.example .env.local
# Éditer .env.local

# Lancer en développement
npm run dev
```

### Base de données

```bash
# Exécuter le script SQL
mysql -u u482325361_immo -p u482325361_immo < database/setup.sql
```

## Variables d'environnement

### Backend (.env)
```
DATABASE_URL=mysql+pymysql://user:password@host:3306/database
ANTHROPIC_API_KEY=sk-ant-...
STRIPE_SECRET_KEY=sk_...
STRIPE_PUBLISHABLE_KEY=pk_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_PRO_MONTHLY=price_...
JWT_SECRET_KEY=your-secret-key
FRONTEND_URL=http://localhost:3000
```

### Frontend (.env.local)
```
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_...
```

## API Endpoints

### Authentification
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `GET /api/auth/me` - Profil utilisateur

### Annonces
- `POST /api/annonces/rewrite` - Générer les 3 versions
- `POST /api/annonces/rewrite-single` - Générer une version
- `GET /api/annonces/usage` - Utilisation du mois
- `GET /api/annonces/history` - Historique
- `GET /api/annonces/{id}` - Détail d'une annonce
- `DELETE /api/annonces/{id}` - Supprimer

### Abonnements
- `POST /api/subscriptions/create-checkout` - Créer session paiement
- `POST /api/subscriptions/portal` - Portail client
- `GET /api/subscriptions/status` - Statut abonnement
- `POST /api/subscriptions/webhook` - Webhook Stripe

## Prompt IA optimisé

Le prompt utilisé pour la réécriture est optimisé pour l'immobilier :

```
Tu es un rédacteur immobilier professionnel avec 15 ans d'expérience.
Ta mission est de réécrire des annonces immobilières pour maximiser l'envie de visite.

Contraintes obligatoires :
- Français impeccable
- Ton professionnel et vendeur
- Pas de promesses exagérées
- Pas de répétitions
- Phrases claires et fluides
- Aucune faute

[...]
```

## Déploiement

### Hébergement recommandé
- **Backend** : Railway, Render, ou VPS
- **Frontend** : Vercel
- **Base de données** : Hostinger MySQL (déjà configuré)

### Production
1. Configurer les variables d'environnement de production
2. Créer les produits Stripe (plan Pro à 19€/mois)
3. Configurer le webhook Stripe vers `/api/subscriptions/webhook`
4. Déployer le backend
5. Déployer le frontend avec `NEXT_PUBLIC_API_URL` vers le backend

## Licence

Projet privé - Tous droits réservés
