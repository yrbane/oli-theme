# ADR 0013 — Réseaux sociaux : icônes embarquées

**Statut :** Accepté — Cycle 2

## Contexte

Le site doit afficher dans son pied de page un widget « Réseaux sociaux »
avec icônes officielles des marques (Facebook, Instagram, X, YouTube,
LinkedIn, TikTok, Pinterest, WhatsApp, Telegram) + un lien email.

Contrainte du projet : **pas de lib externe au runtime** (CDN bloqué dans
certains environnements, on veut un thème autonome).

## Décision

1. **Icônes embarquées** dans `assets/img/icons/social/` au format SVG.
2. **Source des logos brand** : [Simple Icons](https://simpleicons.org)
   (MIT) — collection de référence pour les logos officiels, monocolore,
   1 path par icône.
3. **Source de l'icône Email** : [Material Symbols](https://fonts.google.com/icons)
   (Apache 2.0) — Google ne fournit pas les logos brand mais propose
   l'icône `mail` parfaitement adaptée.
4. **Catalogue figé** dans `Social\SocialLinksRepository::PLATFORMS` :
   liste ordonnée des 10 plateformes avec id, label, fichier d'icône,
   placeholder.
5. **Page admin** `Apparence > Réseaux sociaux` : un champ URL par
   plateforme avec icône à gauche du label.
6. **Rendu front** : macro Lunar `##socialIcons()##` injectée dans
   `templates/partials/footer.html.tpl`. Les SVG sont **inlinés** au
   render (`file_get_contents`) après suppression des attributs `fill`
   pour permettre la colorisation via `currentColor`.
7. **Couleurs au survol** : couleurs de marque officielles (ou dominante
   pour Instagram, qui a un dégradé non reproductible sur SVG monocolore),
   appliquées via les classes BEM modificatrices `.social-links__link--{id}`.

## Conséquences

**Positives :**
- Aucun appel réseau au runtime (CDN, font Google) → autonome, RGPD-friendly
- SVG inlinés → un seul cache navigateur (pas de N requêtes pour N icônes)
- `currentColor` permet à chaque variation CSS de fixer la couleur de base
  (gris doux dans Olikalari) tout en respectant les couleurs de marque au hover
- BEM strict (`.social-links` + `__item` + `__link` + `__link--facebook`) →
  facile à styler / surcharger par variation

**Négatives / compromis :**
- Mise à jour manuelle des icônes : si une marque rebrand (cf. Twitter → X),
  il faut télécharger le nouveau SVG et écraser le fichier
- Instagram en couleur unie au hover (`#E4405F` dominante) plutôt que
  dégradé : compromis acceptable — le dégradé Instagram nécessiterait
  un masque SVG bien plus complexe et moins flexible avec `currentColor`
- 10 fichiers SVG = ~7 ko cumulés au repo. Négligeable

## Sécurité

- `esc_url_raw()` côté admin avec liste blanche de protocoles : `http`,
  `https`, `mailto`, `tel`. Refuse `javascript:`, `data:`, etc.
- `htmlspecialchars($url, ENT_QUOTES, 'UTF-8')` sur les attributs au rendu
- `target="_blank" rel="noopener noreferrer"` sur les liens externes
- Capability `manage_options` + nonce sur le formulaire admin
- Filtrage des SVG par `preg_replace` pour supprimer `fill="..."` avant
  inlining (les SVG Simple Icons sont sains, mais belt-and-braces)

## Alternatives écartées

- **Material Icons de Google partout** : politique brand stricte de Google,
  les logos de marques ne sont pas fournis. Solution incomplète.
- **Font Awesome / Iconify (CDN)** : viole la contrainte « pas de lib
  externe ». Embarquer FA en local = +400 ko de fonts.
- **`<i class="fab fa-facebook">` + sprite SVG** : sprite plus compact mais
  rendu plus complexe (référencement par `<use>` + `xlink:href`). L'inlining
  par macro est plus direct et plus simple à styler.
- **CPT `oli_social`** : surdimensionné — 10 entrées figées suffisent et
  un tableau d'admin est inutile pour un widget figé. Un simple form avec
  10 champs URL est plus rapide à utiliser.
