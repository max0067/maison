"""Service de rédaction IA pour les annonces immobilières."""
import re
from typing import Optional, Tuple
from anthropic import Anthropic

from app.config import get_settings
from app.models import TypeBien, TypeTransaction, Gamme, TonRedaction

settings = get_settings()


def get_prompt_immobilier(
    texte_original: str,
    type_bien: TypeBien,
    type_transaction: TypeTransaction,
    gamme: Gamme,
    ton: TonRedaction,
) -> str:
    """Génère le prompt optimisé pour la rédaction immobilière."""

    ton_instructions = {
        TonRedaction.PROFESSIONNEL: "Rédige une version professionnelle, claire et vendeuse.",
        TonRedaction.COURT: "Rédige une version courte et percutante (max 150 mots).",
        TonRedaction.PREMIUM: "Rédige une version premium émotionnelle qui fait rêver le lecteur.",
    }

    return f"""Tu es un rédacteur immobilier professionnel avec 15 ans d'expérience.
Ta mission est de réécrire des annonces immobilières pour maximiser l'envie de visite.

Contraintes obligatoires :
- Français impeccable
- Ton professionnel et vendeur
- Pas de promesses exagérées
- Pas de répétitions
- Phrases claires et fluides
- Aucune faute

Contexte du bien :
- Type de bien : {type_bien.value}
- Transaction : {type_transaction.value}
- Gamme : {gamme.value.replace('_', ' ')}

Objectifs :
- Mettre en valeur le bien sans survendre
- Donner envie de visiter
- Rester crédible et professionnel

Instructions spécifiques :
{ton_instructions[ton]}

Évite absolument :
- Les expressions vagues ("à découvrir absolument", "rare", "coup de coeur")
- Les phrases trop longues (max 25 mots par phrase)
- Le jargon inutile
- Les superlatifs non justifiés

Format de réponse :
Retourne UNIQUEMENT le texte réécrit, sans commentaires ni explications.

Texte original :
\"\"\"
{texte_original}
\"\"\"
"""


def rewrite_with_claude(
    texte_original: str,
    type_bien: TypeBien,
    type_transaction: TypeTransaction,
    gamme: Gamme,
    ton: TonRedaction,
) -> str:
    """Réécrit une annonce avec Claude (Anthropic)."""
    client = Anthropic(api_key=settings.anthropic_api_key)

    prompt = get_prompt_immobilier(
        texte_original, type_bien, type_transaction, gamme, ton
    )

    message = client.messages.create(
        model="claude-sonnet-4-20250514",
        max_tokens=2000,
        messages=[{"role": "user", "content": prompt}],
    )

    return message.content[0].text.strip()


def rewrite_all_versions(
    texte_original: str,
    type_bien: TypeBien,
    type_transaction: TypeTransaction,
    gamme: Gamme,
) -> Tuple[str, str, str]:
    """Génère les trois versions de l'annonce."""
    client = Anthropic(api_key=settings.anthropic_api_key)

    prompt = f"""Tu es un rédacteur immobilier professionnel avec 15 ans d'expérience.
Ta mission est de réécrire des annonces immobilières pour maximiser l'envie de visite.

Contraintes obligatoires :
- Français impeccable
- Ton professionnel et vendeur
- Pas de promesses exagérées
- Pas de répétitions
- Phrases claires et fluides
- Aucune faute

Contexte du bien :
- Type de bien : {type_bien.value}
- Transaction : {type_transaction.value}
- Gamme : {gamme.value.replace('_', ' ')}

Objectifs :
- Mettre en valeur le bien sans survendre
- Donner envie de visiter
- Rester crédible et professionnel

Évite absolument :
- Les expressions vagues ("à découvrir absolument", "rare", "coup de coeur")
- Les phrases trop longues (max 25 mots par phrase)
- Le jargon inutile
- Les superlatifs non justifiés

À produire (retourne EXACTEMENT ce format) :

===VERSION_PROFESSIONNELLE===
[Une version réécrite professionnelle et claire]
===FIN_PROFESSIONNELLE===

===VERSION_COURTE===
[Une version courte et percutante, max 150 mots]
===FIN_COURTE===

===VERSION_PREMIUM===
[Une version premium émotionnelle qui fait rêver]
===FIN_PREMIUM===

Texte original :
\"\"\"
{texte_original}
\"\"\"
"""

    message = client.messages.create(
        model="claude-sonnet-4-20250514",
        max_tokens=4000,
        messages=[{"role": "user", "content": prompt}],
    )

    response_text = message.content[0].text

    # Parser les trois versions
    def extract_version(text: str, start_tag: str, end_tag: str) -> str:
        pattern = f"{start_tag}(.*?){end_tag}"
        match = re.search(pattern, text, re.DOTALL)
        if match:
            return match.group(1).strip()
        return ""

    version_pro = extract_version(
        response_text, "===VERSION_PROFESSIONNELLE===", "===FIN_PROFESSIONNELLE==="
    )
    version_courte = extract_version(
        response_text, "===VERSION_COURTE===", "===FIN_COURTE==="
    )
    version_premium = extract_version(
        response_text, "===VERSION_PREMIUM===", "===FIN_PREMIUM==="
    )

    return version_pro, version_courte, version_premium
