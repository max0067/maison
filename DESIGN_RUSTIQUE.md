# 🏡 Design Rustique Élégant - Style Maison d'Hôtes Provençale

## ✨ Design adapté à votre image de référence

Le design a été complètement adapté pour correspondre au style **rustique-élégant** de votre image, avec une ambiance **maison d'hôtes provençale** authentique.

---

## 🎨 Palette de couleurs

### Couleurs principales
- **Marron primaire** : `#8b7355` - Couleur des boutons et accents
- **Marron foncé** : `#6f5a42` - Hover states
- **Marron clair** : `#a8917a` - Teintes claires
- **Beige** : `#c9b896` - Couleur secondaire
- **Crème** : `#f5f3ef` - Backgrounds

### Couleurs de texte
- **Texte foncé** : `#3a3a3a`
- **Texte moyen** : `#666666`
- **Texte clair** : `#999999`

### Couleurs fonctionnelles (admin)
- **Succès** : `#5c8a4f` (vert terreux)
- **Warning** : `#d9a84e` (ocre)
- **Danger** : `#c85a54` (rouge brique)

---

## 📝 Typographie

### Frontend
- **Police** : Georgia, Times New Roman (serif)
- **Style** : Élégant, classique, chaleureux
- **Letterspacing** : Espacé pour un look raffiné

### Admin
- **Police** : Système (Segoe UI, Roboto) pour la lisibilité
- **Logo** : Georgia avec couleur marron
- **Style** : Professionnel mais cohérent

---

## 🌐 Site Public (Frontend)

### Hero Section
- **Fond** : Beige `#c9b896` (au lieu d'indigo/violet)
- **Overlay** : Dégradé noir subtil
- **Typographie** : Grande, serif, espacée
- **Ambiance** : Maison en pierre provençale

### Cards des Chambres
- **Fond** : Blanc pur
- **Bordures** : Beige clair `#e8e4dd`
- **Ombres** : Très subtiles et douces
- **Prix** : Marron `#8b7355` (pas de gradient)
- **Hover** : Translation douce vers le haut

### Formulaire de Réservation
- **Container** : Blanc avec ombre douce
- **Inputs** : Bordures beiges
- **Focus** : Bordure marron + shadow subtil
- **Bouton** : Marron `#8b7355` solide
- **Total** : Fond crème

### Style général
- Espacement généreux
- Ombres très subtiles (pas de shadow-xl agressifs)
- Bordures arrondies modérées (8-12px)
- Transitions douces

---

## 🔐 Backoffice Admin

### Header
- **Fond** : Blanc pur
- **Logo** : Marron avec Georgia serif
- **Navigation** : Gris avec hover beige
- **Active** : Marron solide

### Dashboard
- **Stats Cards** : Gradient beige/marron très subtil
- **Chiffres** : Marron `#8b7355` (pas de gradient text)
- **Icons** : Fond beige transparent

### Tableaux
- **Header** : Fond beige `#f5f3ef`
- **Bordures** : Beige `#e8e4dd`
- **Hover** : Fond beige très clair

### Badges
- **En attente** : Ocre `#d9a84e`
- **Confirmée** : Vert terreux `#5c8a4f`
- **Annulée** : Rouge brique `#c85a54`

### Boutons
- **Primaire** : Marron solide (pas de gradient)
- **Secondaire** : Gris clair
- **Danger** : Rouge brique

---

## 🎯 Différences clés

### Avant (Style moderne)
- ❌ Gradients indigo/violet colorés
- ❌ Couleurs vives (bleu, violet, rose)
- ❌ Typographie sans-serif moderne
- ❌ Ombres marquées
- ❌ Style tech/startup

### Après (Style rustique)
- ✅ Tons beiges/marrons terreux
- ✅ Couleurs chaleureuses et douces
- ✅ Typographie serif élégante
- ✅ Ombres subtiles
- ✅ Style maison d'hôtes authentique

---

## 📱 Responsive Design

Le design s'adapte parfaitement à tous les écrans :
- **Mobile** (< 480px) : Colonne unique, navigation empilée
- **Tablet** (< 768px) : Grids adaptés, espacement réduit
- **Desktop** (> 768px) : Layout complet

---

## 🚀 Comment personnaliser

### Changer la couleur principale

**Dans `assets/css/style.css`** (Frontend) :
```css
:root {
    --primary-color: #VOTRE_COULEUR;  /* Au lieu de #8b7355 */
}
```

**Dans `assets/css/admin.css`** (Admin) :
```css
:root {
    --primary: #VOTRE_COULEUR;  /* Au lieu de #8b7355 */
}
```

### Changer la typographie

**Frontend** :
```css
body {
    font-family: 'Votre-Police', 'Georgia', serif;
}
```

### Ajuster les arrondis

```css
:root {
    --radius-sm: 4px;   /* Petits éléments */
    --radius-md: 8px;   /* Moyen */
    --radius-lg: 12px;  /* Cards */
}
```

---

## ✨ Ambiance créée

### Mots-clés du design
- 🏡 **Rustique** - Authentique, naturel
- 💎 **Élégant** - Raffiné, soigné
- 🌾 **Provençal** - Sud de la France, chaleur
- 🤎 **Chaleureux** - Accueillant, confortable
- 📜 **Intemporel** - Classique, durable

### Émotions évoquées
- Sérénité et repos
- Authenticité et tradition
- Luxe discret
- Chaleur méditerranéenne
- Élégance naturelle

---

## 🎨 Exemples d'utilisation des couleurs

### Boutons
```css
background: #8b7355;  /* Marron primaire */
color: white;
```

### Backgrounds alternatifs
```css
background: #f5f3ef;  /* Crème pour sections */
```

### Texte sur fond clair
```css
color: #3a3a3a;  /* Texte principal */
color: #666666;  /* Texte secondaire */
```

### Bordures
```css
border: 1px solid #e8e4dd;  /* Beige clair */
```

---

## 📸 Résultat final

Le design correspond maintenant **exactement** à votre image de référence :
- ✅ Tons beiges/marrons comme sur la photo
- ✅ Ambiance maison d'hôtes provençale
- ✅ Typographie serif élégante
- ✅ Style rustique-chic authentique
- ✅ Cohérence visuelle frontend/admin

---

**Note** : Le design a été pensé pour évoquer une véritable maison d'hôtes de charme dans le sud de la France, avec des matériaux naturels (pierre, bois) et une ambiance chaleureuse et authentique.

C'est exactement le style de votre image de référence ! 🏡✨
