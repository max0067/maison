"""Service de calcul de score qualité pour les annonces."""
import re
from typing import Tuple

# Mots faibles à éviter dans une annonce immobilière
MOTS_FAIBLES = [
    "très",
    "vraiment",
    "absolument",
    "tout",
    "petit",
    "grand",
    "beau",
    "belle",
    "joli",
    "jolie",
    "super",
    "magnifique",
    "exceptionnel",
    "exceptionnelle",
    "unique",
    "rare",
    "coup de coeur",
    "coup de cœur",
    "à découvrir",
    "à voir",
    "ne manquez pas",
    "idéal",
    "parfait",
    "parfaite",
    "incroyable",
    "sensationnel",
    "extraordinaire",
    "splendide",
    "somptueux",
    "somptueuse",
    "époustouflant",
    "sublime",
    "merveilleux",
    "merveilleuse",
    "fabuleux",
    "fabuleuse",
]

# Vocabulaire riche immobilier
VOCABULAIRE_RICHE = [
    "lumineux",
    "lumineuse",
    "spacieux",
    "spacieuse",
    "fonctionnel",
    "fonctionnelle",
    "traversant",
    "traversante",
    "orientation",
    "exposition",
    "prestations",
    "standing",
    "rénovation",
    "rénové",
    "rénovée",
    "contemporain",
    "contemporaine",
    "cachet",
    "charme",
    "volume",
    "volumes",
    "parquet",
    "moulures",
    "cheminée",
    "terrasse",
    "balcon",
    "loggia",
    "jardin",
    "garage",
    "parking",
    "cave",
    "cellier",
    "dressing",
    "suite parentale",
    "double vitrage",
    "isolation",
    "chauffage",
    "climatisation",
    "ascenseur",
    "gardien",
    "digicode",
    "interphone",
    "sécurisé",
    "sécurisée",
    "calme",
    "résidentiel",
    "résidentielle",
    "proche",
    "proximité",
    "transports",
    "commerces",
    "écoles",
    "commodités",
    "environnement",
    "quartier",
    "secteur",
    "emplacement",
    "vue",
    "dégagé",
    "dégagée",
    "sans vis-à-vis",
]


def calculer_score_clarte(texte: str) -> float:
    """
    Calcule le score de clarté basé sur la longueur des phrases.
    - Phrases courtes (< 20 mots) : bonus
    - Phrases moyennes (20-30 mots) : neutre
    - Phrases longues (> 30 mots) : malus
    """
    phrases = re.split(r"[.!?]+", texte)
    phrases = [p.strip() for p in phrases if p.strip()]

    if not phrases:
        return 50.0

    scores = []
    for phrase in phrases:
        mots = len(phrase.split())
        if mots <= 15:
            scores.append(100)
        elif mots <= 20:
            scores.append(90)
        elif mots <= 25:
            scores.append(75)
        elif mots <= 30:
            scores.append(60)
        else:
            scores.append(max(30, 100 - (mots - 30) * 2))

    return sum(scores) / len(scores)


def calculer_score_attractivite(texte: str) -> float:
    """
    Calcule le score d'attractivité basé sur :
    - Présence de vocabulaire riche (+)
    - Absence de mots faibles (+)
    - Structure logique (+)
    """
    texte_lower = texte.lower()
    mots = texte_lower.split()
    total_mots = len(mots)

    if total_mots == 0:
        return 50.0

    # Compter les mots faibles (malus)
    mots_faibles_count = 0
    for mot_faible in MOTS_FAIBLES:
        mots_faibles_count += texte_lower.count(mot_faible.lower())

    # Compter le vocabulaire riche (bonus)
    vocab_riche_count = 0
    for vocab in VOCABULAIRE_RICHE:
        if vocab.lower() in texte_lower:
            vocab_riche_count += 1

    # Calculer le score
    score = 70  # Score de base

    # Bonus pour vocabulaire riche (max +20)
    vocab_bonus = min(20, vocab_riche_count * 2)
    score += vocab_bonus

    # Malus pour mots faibles (max -30)
    faibles_malus = min(30, mots_faibles_count * 5)
    score -= faibles_malus

    # Bonus pour longueur appropriée (150-400 mots)
    if 150 <= total_mots <= 400:
        score += 10
    elif 100 <= total_mots < 150 or 400 < total_mots <= 500:
        score += 5

    return max(0, min(100, score))


def calculer_score_lisibilite(texte: str) -> float:
    """
    Calcule le score de lisibilité basé sur :
    - Variation de la longueur des phrases
    - Structure en paragraphes
    - Utilisation de la ponctuation
    """
    # Compter les paragraphes
    paragraphes = [p.strip() for p in texte.split("\n\n") if p.strip()]
    nb_paragraphes = len(paragraphes)

    # Compter les phrases
    phrases = re.split(r"[.!?]+", texte)
    phrases = [p.strip() for p in phrases if p.strip()]
    nb_phrases = len(phrases)

    if nb_phrases == 0:
        return 50.0

    # Calculer la variance des longueurs de phrases (diversité = bien)
    longueurs = [len(p.split()) for p in phrases]
    moyenne = sum(longueurs) / len(longueurs)
    variance = sum((l - moyenne) ** 2 for l in longueurs) / len(longueurs)

    score = 70  # Score de base

    # Bonus pour structure en paragraphes
    if nb_paragraphes >= 3:
        score += 10
    elif nb_paragraphes >= 2:
        score += 5

    # Bonus pour variation (pas trop monotone, pas trop chaotique)
    if 10 <= variance <= 50:
        score += 10
    elif 5 <= variance < 10 or 50 < variance <= 80:
        score += 5

    # Bonus pour ponctuation variée
    if ":" in texte:
        score += 3
    if "," in texte:
        score += 2
    if "-" in texte or "–" in texte:
        score += 2

    # Malus pour répétitions
    mots = texte.lower().split()
    mots_uniques = set(mots)
    ratio_repetition = len(mots_uniques) / len(mots) if mots else 0

    if ratio_repetition < 0.4:
        score -= 15
    elif ratio_repetition < 0.5:
        score -= 10
    elif ratio_repetition < 0.6:
        score -= 5

    return max(0, min(100, score))


def calculer_scores(texte: str) -> Tuple[float, float, float, float]:
    """
    Calcule tous les scores pour un texte.
    Retourne: (clarte, attractivite, lisibilite, global)
    """
    clarte = calculer_score_clarte(texte)
    attractivite = calculer_score_attractivite(texte)
    lisibilite = calculer_score_lisibilite(texte)

    # Score global = moyenne pondérée
    global_score = (clarte * 0.3 + attractivite * 0.4 + lisibilite * 0.3)

    return (
        round(clarte, 1),
        round(attractivite, 1),
        round(lisibilite, 1),
        round(global_score, 1),
    )


def get_score_color(score: float) -> str:
    """Retourne la couleur correspondant au score."""
    if score >= 75:
        return "green"
    elif score >= 50:
        return "orange"
    else:
        return "red"
