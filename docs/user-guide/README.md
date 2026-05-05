# Guide utilisateur — oli-theme

Bienvenue. Ce dossier rassemble la documentation **éditeur** du thème oli-theme : tout ce qu'un rédacteur doit savoir pour publier, traduire et diffuser le contenu sur un site Oli, sans toucher au code.

## Par où commencer ?

→ [`getting-started.md`](getting-started.md) — **10 minutes pour publier votre première page multilingue**.

## Guides par domaine

| Domaine | Guide |
|--------|-------|
| Système multilingue (créer une langue, lier les traductions) | [`../multilingue.md`](../multilingue.md) |
| Menus (créer un menu par langue, drawer mobile) | [`../navigation.md`](../navigation.md) |
| Carrousel d'accueil (CPT `oli_slide`) | [`../slides.md`](../slides.md) |
| Événements (CPT `oli_event`, dates, inscription) | [`../events.md`](../events.md) |
| SEO (métabox per-post, score, redirections, sitemap) | [`../seo.md`](../seo.md) |
| Formulaire de contact (`[oli_contact_form]`) | [`../contact.md`](../contact.md) |
| Identité du site (logo, langues, réseaux, footer) | [`../settings.md`](../settings.md) |

## FAQ rapide

**Q. Comment ajouter une nouvelle langue ?**
A. Voir `multilingue.md`. La langue apparait automatiquement dans les locations de menus, dans la métabox SEO, dans les filtres de slides/événements.

**Q. Le carousel d'accueil ne s'affiche pas.**
A. Vérifier qu'au moins un slide `oli_slide` est publié, étiqueté avec la langue courante, et non expiré. Le carousel n'apparait QUE sur la page d'accueil (`is_front_page()`).

**Q. Comment activer la page Settings ?**
A. Elle apparait automatiquement sous `Apparence > Identité du site` à partir de la version 1.0.0-alpha.9. Capability requise : `manage_options`.

**Q. La métabox SEO n'apparait pas.**
A. Elle s'affiche sur les écrans d'édition de `post`, `page`, `oli_event`. Si elle est masquée, vérifier les **Options de l'écran** (en haut à droite de l'admin WP).

**Q. Comment ajouter une redirection 301 ?**
A. **Outils > Redirections**. Saisir l'URL source (chemin uniquement, ex. `/ancien-lien/`) et l'URL cible (absolue ou chemin), choisir le code (301 ou 410). La redirection est active immédiatement.

**Q. Comment configurer l'email du formulaire de contact ?**
A. Via WP-CLI : `wp option update oli_contact_email "contact@olikalari.com"`. Ou (à terme via la page Settings → onglet Contact, livré dans le cycle 2 avec UI riche).

## Support

- Code source : <https://github.com/yrbane/oli-theme>
- Issues : <https://github.com/yrbane/oli-theme/issues>
