# Réseaux sociaux

Module `Social` : sélecteur d'URLs de réseaux sociaux + widget d'icônes
intégré au pied de page de toutes les pages.

Configuration : **Apparence > Réseaux sociaux**.

---

## Plateformes supportées

10 réseaux pré-câblés (dans cet ordre d'affichage) :

| Plateforme | Source de l'icône | Placeholder |
|------------|-------------------|-------------|
| Facebook | Simple Icons | `https://www.facebook.com/MaPage` |
| Instagram | Simple Icons | `https://www.instagram.com/monpseudo` |
| X (Twitter) | Simple Icons | `https://x.com/monpseudo` |
| YouTube | Simple Icons | `https://www.youtube.com/@MaChaine` |
| LinkedIn | Simple Icons | `https://www.linkedin.com/in/monprofil` |
| TikTok | Simple Icons | `https://www.tiktok.com/@monpseudo` |
| Pinterest | Simple Icons | `https://www.pinterest.fr/monpseudo` |
| WhatsApp | Simple Icons | `https://wa.me/33612345678` |
| Telegram | Simple Icons | `https://t.me/monpseudo` |
| Email | Material Symbols | `mailto:contact@example.com` |

### Pourquoi pas Material Icons partout ?

Google Material Icons **ne fournit pas** les logos de marques (politique
brand). On utilise donc [Simple Icons](https://simpleicons.org) (MIT) pour
les logos officiels, et Material Symbols pour ce qui n'est pas brand-spécifique
(ici l'icône Email).

Toutes les icônes sont **embarquées** dans `assets/img/icons/social/` —
aucune dépendance réseau au runtime.

---

## Configuration

Dans **Apparence > Réseaux sociaux** :

- Renseigner l'URL de chaque profil que vous voulez afficher
- Laisser vide les plateformes que vous n'utilisez pas — elles ne seront
  **pas affichées** sur le site (pas d'icône grise inactive)
- Cliquer **Enregistrer**

URLs par défaut tant que rien n'est sauvegardé :

| | URL pré-remplie |
|---|---|
| Facebook | `https://www.facebook.com/oli.kalari/` |
| Instagram | `https://www.instagram.com/oli_kalari/?hl=en` |
| YouTube | `https://www.youtube.com/channel/UCfR1dfixUpEzBsFW81N6qsQ?view_as=subscriber` |
| Autres | vides |

Au premier save, les valeurs enregistrées prennent la main.

---

## Rendu front

Les icônes apparaissent **automatiquement dans le pied de page** de
toutes les pages, via la macro Lunar `##socialIcons()##` injectée dans
`templates/partials/footer.html.tpl`.

```html
<ul class="social-links" aria-label="Réseaux sociaux">
    <li class="social-links__item">
        <a class="social-links__link social-links__link--facebook"
           href="..." target="_blank" rel="noopener noreferrer"
           aria-label="Facebook" title="Facebook">
            <svg>…</svg>
        </a>
    </li>
    …
</ul>
```

### Couleurs au survol

- **Hors hover** : icône en gris doux (héritée du footer via `currentColor`)
- **Au hover** : couleur de marque officielle (Instagram = dominante rose,
  pas de dégradé reproductible sur SVG monocolore)

| Plateforme | Hover |
|------------|-------|
| Facebook | `#1877F2` |
| Instagram | `#E4405F` (dominante) |
| X | `#000000` |
| YouTube | `#FF0000` |
| LinkedIn | `#0A66C2` |
| TikTok | `#000000` |
| Pinterest | `#BD081C` |
| WhatsApp | `#25D366` |
| Telegram | `#26A5E4` |
| Email | inherit (pas de marque) |

### Sécurité

- `esc_url_raw()` côté admin avec liste blanche de protocoles : `http`,
  `https`, `mailto`, `tel`
- `htmlspecialchars()` sur les attributs au rendu
- `target="_blank" rel="noopener noreferrer"` pour la sécurité tabnabbing

---

## Personnaliser les couleurs au hover

Modifier les sélecteurs `.social-links__link--{platform}:hover` dans
`assets/css/base.css` ou dans une variation CSS qui les surcharge.

Exemple pour passer Instagram au violet :

```css
.social-links__link--instagram:hover {
    color: #833AB4;
}
```

---

## Personnaliser les icônes

Les SVG sont dans `assets/img/icons/social/{platform}.svg`. Pour remplacer
ou ajouter une plateforme :

1. Déposer le SVG (1 path, monocolore, fill modifiable) dans le dossier
2. Ajouter une entrée dans `Social\SocialLinksRepository::PLATFORMS`
3. Optionnel : ajouter une couleur hover dans `assets/css/base.css`

L'admin et le front la prennent en compte automatiquement.
