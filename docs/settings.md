# Settings (Identité du site)

> **Depuis le Cycle 2 (ADR 0014)**, toute la configuration du thème est rassemblée
> sur une **page unique à onglets** : **Apparence > Réglages du thème**
> (`themes.php?page=oli-theme-settings`), groupée par thème (Identité & Marque,
> Apparence, Contenu, Contact, SEO). Les réglages décrits ici correspondent aux
> sous-onglets des groupes **Identité & Marque**, **Contact** et **SEO**. Les
> anciennes URLs sont redirigées en 301 vers le bon onglet.

Le thème expose ces options sans toucher au code.

## Sections (sous-onglets)

| Sous-onglet | Groupe | Contenu |
|-------------|--------|---------|
| Identité visuelle | Identité & Marque | Logo, bannière desktop, bannière mobile, alt text par langue |
| Langues | Identité & Marque | Liste des langues activées, langue par défaut, comportement de fallback (`home` / `show_source` / `message`) |
| Réseaux sociaux | Identité & Marque | Géré par le module dédié (`SocialAdminPage`, 10 plateformes + icônes, option `oli_social_links`) — voir [`docs/social.md`](social.md) |
| Pied de page | Identité & Marque | Mentions légales par langue, template copyright, toggles `showLegal`/`showSocial`/`showMenu` |
| Contact | Contact | Email destinataire, auto-réponse, logging |
| Réglages SEO | SEO | Image OG par défaut, Twitter handle, Organisation Schema.org, sitemap, robots.txt custom |

> ⚠️ L'ancien onglet « Réseaux sociaux » de cette page (5 réseaux sans icônes,
> stocké dans `oli_theme_settings[social]`) était du code mort non affiché au
> front : il a été **supprimé** (ADR 0014). La saisie des réseaux se fait
> désormais uniquement via le module dédié.

## Stockage

Toutes les options sont **persistées dans une option WP unique** : `oli_theme_settings` (array). Lecture/écriture via `OliTheme\Settings\ThemeSettingsModel`.

```php
use OliTheme\Theme;
use OliTheme\Settings\ThemeSettingsModelInterface;

$settings = Theme::container()->get(ThemeSettingsModelInterface::class);
$bag = $settings->all();   // SettingsBag immuable

echo $bag->languages->default;          // 'fr'
echo $bag->social->facebook ?? '';
echo $bag->seo->organizationName ?? '';
```

## API

### `ThemeSettingsModelInterface`

```php
interface ThemeSettingsModelInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): bool;
    public function all(): SettingsBag;
}
```

### `SettingsBag`

DTO `final readonly` agrégeant les 6 sous-bags :

- `BannerSettings $banner`
- `FooterSettings $footer`
- `SocialSettings $social`
- `LanguagesSettings $languages`
- `ContactSettings $contact`
- `SeoSettings $seo`

`SettingsBag::default()` produit un bag neutre (langues `['fr']`, défaut `'fr'`, fallback `home`, toggles à `true`).

## Hydratation

`ThemeSettingsModel::all()` lit `oli_theme_settings`, hydrate chaque sous-DTO depuis sa section (`banner`, `footer`, etc.). Les valeurs absentes utilisent les défauts du `SettingsBag::default()`.

Cela garantit que `all()` retourne **toujours** un SettingsBag complet, même sur une fresh install — les controllers en aval n'ont pas à gérer les nullables des options.

## Modifier via WP-CLI

```bash
wp option update oli_theme_settings '{"languages":{"enabled":["fr","en"],"default":"fr","fallbackBehavior":"home"}}' --format=json
```

## Pour les développeurs

### Architecture

```
src/Settings/
├── BannerSettings.php           DTO immuable
├── FooterSettings.php           DTO immuable
├── SocialSettings.php           DTO immuable
├── LanguagesSettings.php        DTO immuable + constantes FALLBACK_*
├── ContactSettings.php          DTO immuable
├── SeoSettings.php              DTO immuable
├── SettingsBag.php              agrégateur + ::default()
├── ThemeSettingsModel.php (+ I) lecture/écriture
├── ThemeSettingsPage.php        page admin (Settings API native)
└── SettingsModule.php           services + hooks admin_menu/admin_init
```

### Hooks WordPress

- `admin_menu` — `ThemeSettingsPage::register()` (ajoute la page sous `Apparence`).
- `admin_init` — `ThemeSettingsPage::registerSettings()` (déclare les sections via `register_setting` + `add_settings_section`).

### Sanitization

`ThemeSettingsPage::sanitize()` est branchée comme `sanitize_callback` du `register_setting`. Elle merge l'input utilisateur avec les options existantes pour éviter d'écraser les sections non touchées par le formulaire courant.

Pour le cycle 1, le rendu des champs custom (réseaux sociaux, langues, etc.) est délégué aux fonctions WP standards `do_settings_sections()` capturées en buffer puis injectées dans le template Lunar. Les composants UI riches (uploader média, drag-drop des langues) sont **reportés à un cycle 2**.

## Sécurité

- Capability requise : `manage_options` (admin uniquement).
- `register_setting` gère automatiquement le nonce WP via `settings_fields()`.
- Le merge dans `sanitize` empêche les écrasements croisés entre tabs.
