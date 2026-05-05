# Formulaire de contact

Le thème expose un formulaire de contact custom OOP, sécurisé (CSRF + honeypot + time-trap + rate-limit) et multilingue.

## Insertion

### Via shortcode

Sur n'importe quelle page WP :

```
[oli_contact_form]
```

### Via include Lunar (templates personnalisés)

Le partial `templates/partials/contact-form.html.tpl` peut aussi être inclus directement, à condition de fournir manuellement les variables (`nonce`, `timestamp`, `actionUrl`, `redirect`, `errors`, `success`, `lang`). Privilégier le shortcode.

## Configuration (options WP)

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `oli_contact_email` | string | `get_bloginfo('admin_email')` | Email destinataire des messages |
| `oli_contact_autoreply` | string | `''` | `'1'` pour activer l'auto-réponse |
| `oli_contact_autoreply_body` | string | `'Merci pour votre message…'` | Corps de l'auto-réponse |
| `oli_contact_logging` | string | `''` | `'1'` pour archiver les soumissions dans le CPT `oli_contact_log` |

À configurer via WP-CLI ou (à terme) via la page Settings (Plan 9).

```bash
wp option update oli_contact_email "contact@olikalari.com"
wp option update oli_contact_autoreply "1"
wp option update oli_contact_logging "1"
```

## Sécurité

Le pipeline `ContactFormController::handle()` exécute dans cet ordre :

1. **Vérification du nonce WP** (`oli_contact`) — bloque les requêtes cross-site (CSRF). Échec → `wp_die(403)`.
2. **Honeypot** — un champ caché en CSS (`position: absolute; left: -10000px;`) ne doit jamais être rempli ; sinon `error=spam_detected`.
3. **Time-trap** — `now - timestamp ≥ 3 secondes` ; sinon `error=too_fast` (un humain met plus de 3s à remplir un formulaire).
4. **Rate-limit** — `ContactRateLimiter` autorise 3 envois par IP / 15 minutes via `set_transient`.
5. **Validation** — `name` 2-100 chars, `email` via `is_email()`, `subject` ≤ 150, `message` 10-5000.
6. **Sanitization** — `sanitize_text_field` / `sanitize_email` / `sanitize_textarea_field`.
7. **Envoi** — `wp_mail` avec `Reply-To: <expéditeur>`.
8. **Auto-réponse** (optionnelle) — `wp_mail` à l'expéditeur.
9. **Log** (optionnel) — `wp_insert_post` dans le CPT `oli_contact_log` (statut `private`, taxonomie `language`).
10. **Redirect** — vers `_oli_redirect` avec `?contact=ok` ou `?errors=...`.

## Logs

Le CPT `oli_contact_log` est non-public, accessible via `Outils > Logs Contact`. Capability `create_posts` interdite (les logs ne peuvent être créés que par le pipeline). Méta-données stockées :

- `_oli_contact_name`
- `_oli_contact_email`
- `_oli_contact_ip`
- `_oli_contact_timestamp`

## Pour les développeurs

### Architecture

```
src/Contact/
├── ContactSubmission.php           DTO immuable
├── ContactValidationResult.php     DTO immuable
├── ContactFormModel.php (+ I)      validate + sanitize
├── ContactRateLimiter.php (+ I)    transients WP
├── ContactMailer.php (+ I)         wp_mail + reply-to + auto-reply
├── ContactLogCpt.php               CPT oli_contact_log
├── ContactLogModel.php (+ I)       wp_insert_post + meta
├── ContactFormController.php (+ I) orchestration sécurisée
├── ContactShortcode.php            [oli_contact_form]
└── ContactModule.php               services + hooks
```

### Hooks WordPress

- `init` — enregistre le CPT et le shortcode.
- `admin_post_oli_contact` + `admin_post_nopriv_oli_contact` — déclenchent `ContactFormController::handle($_POST)`.

### Filtre / extension

Pour personnaliser le pipeline, étendre les classes via le `Container` dans le thème enfant (réservé à un cycle ultérieur).
