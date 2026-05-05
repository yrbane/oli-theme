# ADR 0009 — Formulaire de contact custom (vs CF7 / Gravity Forms / WPForms)

**Statut :** accepté
**Date :** 2026-05-06
**Contexte :** Plan 8 — Module Contact.

## Décision

Implémenter un **formulaire de contact custom OOP/TDD** intégré au thème, plutôt qu'utiliser un plugin tiers (Contact Form 7, Gravity Forms, WPForms, Ninja Forms).

## Périmètre

- DTOs immuables `ContactSubmission` + `ContactValidationResult`.
- `ContactFormModel` : validation (8 règles) + sanitization (4 helpers WP).
- `ContactRateLimiter` : 3 envois par IP / 15 min via transients.
- `ContactMailer` : `wp_mail` avec `Reply-To` correct + auto-réponse optionnelle.
- `ContactLogCpt` + `ContactLogModel` : archivage optionnel des soumissions (CPT non-public).
- `ContactFormController` : pipeline sécurisé (CSRF + honeypot + time-trap + rate-limit + validation + sanitize + send + log + redirect).
- `ContactShortcode` : `[oli_contact_form]` rend le partial Lunar.
- Template `templates/partials/contact-form.html.tpl` accessible (labels, autocomplete, focus visible, errors aria-live).
- CSS `assets/css/contact.css` BEM responsive.
- JS `assets/js/contact-form.js` progressive enhancement (UX, no double-click).

## Alternatives rejetées

### Contact Form 7 (CF7)

- ✅ Plugin de référence WP, gratuit, mature.
- ❌ Configuration via shortcode-DSL (`[your-name]`, `[your-email]`, …) lourde et non-typée.
- ❌ Multilingue requiert WPML/Polylang (notre système custom Plan 2 n'est pas reconnu).
- ❌ Spam : nécessite des plugins tiers (Akismet, reCAPTCHA Google) — incompatible avec notre approche zéro-dépendance + RGPD.
- ❌ Pas de tests unitaires possibles côté thème (tout est en runtime/admin).

### Gravity Forms / WPForms

- ✅ UI admin riche (drag & drop builder).
- ❌ Plugins commerciaux (licence annuelle).
- ❌ Couplage fort à leur stockage en base + emailers propriétaires.
- ❌ Surface de code énorme pour un besoin simple (un formulaire de contact).

### Ninja Forms

- ✅ Open source.
- ❌ Couplage avec leur API REST.
- ❌ Multilingue non natif (couche tierce requise).

## Conséquences

### Avantages

- ✅ **Zéro dépendance plugin** — cohérent avec la direction du thème (cf. ADR 0003, 0006, 0007, 0008).
- ✅ **Sécurité auditée et testée** — chaque protection (CSRF, honeypot, time-trap, rate-limit, validation, sanitization) a son test unitaire.
- ✅ **Multilingue natif** — les libellés du template Lunar sont en français pour le cycle 1 ; localisation ultérieure simple.
- ✅ **Logs optionnels** — désactivés par défaut (RGPD-friendly), activables par `wp option update oli_contact_logging 1`.
- ✅ **Auto-réponse optionnelle** — désactivée par défaut.
- ✅ **Tests TDD** — 9 classes dont chacune est testée isolément (~28 tests dédiés au module).
- ✅ **Pipeline OOP** — `ContactFormController` est testable de bout en bout via mocks d'interfaces.
- ✅ **DI propre** — services injectables, mockables, étendables via le `Container`.
- ✅ **Extension** — un site Oli peut overrider une factory du `Container` pour spécialiser un comportement (ex. envoyer via SendGrid au lieu de `wp_mail`).

### Inconvénients

- ❌ **Surface code à maintenir** : ~9 classes de production + ~10 classes de tests. Charge de maintenance modérée.
- ❌ **Pas de UI builder admin** — l'éditeur ne peut pas réorganiser les champs sans toucher au code. Acceptable car le besoin est un seul formulaire stable (contact). Pour des formulaires variables (sondages, devis), un plugin tiers serait plus approprié.
- ❌ **Spam protection minimale** — honeypot + time-trap suffisent pour ~95% des bots, mais pas contre des attaques humaines ou OCR-driven. Si nécessaire, une intégration Akismet ou hCaptcha pourra être ajoutée en cycle 2 (sans casser le contrat).

## Choix techniques internes

- **DTOs `final readonly`** — immutabilité totale, pas de risque de mutation pendant le pipeline.
- **Interface narrow par classe** — `ContactFormModelInterface`, `ContactRateLimiterInterface`, `ContactMailerInterface`, `ContactLogModelInterface`, `ContactFormControllerInterface`. Permet le mocking PHPUnit + ouvre l'extension via DI.
- **Clock injectable** dans `ContactFormModel` — permet de tester le time-trap sans `time()` réel.
- **Transients pour rate-limit** plutôt qu'une table dédiée — léger, expirable automatiquement, suffit pour un site PME.
- **CPT `oli_contact_log` non-public** avec `create_posts` interdit — les logs ne peuvent être créés que par le pipeline, pas via l'UI.
- **JS progressive enhancement** — le formulaire fonctionne sans JS (POST natif vers `admin-post.php`).

## Liens

- Spec : `docs/superpowers/specs/2026-05-05-oli-theme-design.md` § 4.2.
- Implémentation : `src/Contact/` (~9 classes de prod + 5 interfaces).
- Tests : `tests/Unit/Contact/` (~10 fichiers, ~28 tests).
- Doc utilisateur : `docs/contact.md`.
