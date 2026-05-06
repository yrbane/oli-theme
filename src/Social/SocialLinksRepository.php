<?php

declare(strict_types=1);

namespace OliTheme\Social;

/**
 * Lecture/écriture des URLs des réseaux sociaux.
 *
 * Stocke un tableau associatif `[platform => url]` dans l'option WP
 * `oli_social_links`. Les plateformes supportées sont définies dans la
 * constante {@see PLATFORMS} (chaque plateforme = id + label + slug
 * utilisé pour retrouver l'icône SVG dans `assets/img/icons/social/`).
 *
 * @package OliTheme\Social
 *
 * @since 1.0.0
 */
final class SocialLinksRepository
{
    public const OPTION = 'oli_social_links';

    /**
     * URLs par défaut tant que l'admin n'a rien enregistré.
     * Une fois l'option sauvegardée (même partiellement), ces valeurs ne
     * s'appliquent plus — l'utilisateur a la main.
     *
     * @var array<string, string>
     */
    private const DEFAULTS = [
        'facebook'  => 'https://www.facebook.com/oli.kalari/',
        'instagram' => 'https://www.instagram.com/oli_kalari/?hl=en',
        'youtube'   => 'https://www.youtube.com/channel/UCfR1dfixUpEzBsFW81N6qsQ?view_as=subscriber',
    ];

    /**
     * Plateformes supportées, dans l'ordre d'affichage.
     *
     * Chaque entrée :
     *   - id        : clé interne (utilisée dans l'option et le DOM)
     *   - label     : libellé humain pour l'admin et aria-label
     *   - icon      : nom du fichier SVG dans `assets/img/icons/social/`
     *   - placeholder : exemple d'URL pour le champ admin
     *
     * @var list<array{id: string, label: string, icon: string, placeholder: string}>
     */
    public const PLATFORMS = [
        ['id' => 'facebook',  'label' => 'Facebook',  'icon' => 'facebook.svg',  'placeholder' => 'https://www.facebook.com/MaPage'],
        ['id' => 'instagram', 'label' => 'Instagram', 'icon' => 'instagram.svg', 'placeholder' => 'https://www.instagram.com/monpseudo'],
        ['id' => 'x',         'label' => 'X (Twitter)', 'icon' => 'x.svg',       'placeholder' => 'https://x.com/monpseudo'],
        ['id' => 'youtube',   'label' => 'YouTube',   'icon' => 'youtube.svg',   'placeholder' => 'https://www.youtube.com/@MaChaine'],
        ['id' => 'linkedin',  'label' => 'LinkedIn',  'icon' => 'linkedin.svg',  'placeholder' => 'https://www.linkedin.com/in/monprofil'],
        ['id' => 'tiktok',    'label' => 'TikTok',    'icon' => 'tiktok.svg',    'placeholder' => 'https://www.tiktok.com/@monpseudo'],
        ['id' => 'pinterest', 'label' => 'Pinterest', 'icon' => 'pinterest.svg', 'placeholder' => 'https://www.pinterest.fr/monpseudo'],
        ['id' => 'whatsapp',  'label' => 'WhatsApp',  'icon' => 'whatsapp.svg',  'placeholder' => 'https://wa.me/33612345678'],
        ['id' => 'telegram',  'label' => 'Telegram',  'icon' => 'telegram.svg',  'placeholder' => 'https://t.me/monpseudo'],
        ['id' => 'email',     'label' => 'Email',     'icon' => 'email.svg',    'placeholder' => 'mailto:contact@example.com'],
    ];

    /**
     * @return array<string, string> map id → URL (vide pour les non-renseignés).
     */
    public function all(): array
    {
        // Sentinelle pour distinguer « option absente » de « option vide » :
        // tant que l'admin n'a jamais sauvegardé, get_option retourne false.
        $raw = get_option(self::OPTION, false);

        if ($raw === false) {
            // Première utilisation : on initialise avec les valeurs par défaut.
            $raw = self::DEFAULTS;
        }
        if (!\is_array($raw)) {
            $raw = [];
        }

        $out = [];
        foreach (self::PLATFORMS as $p) {
            $url = isset($raw[$p['id']]) && \is_string($raw[$p['id']]) ? trim($raw[$p['id']]) : '';
            $out[$p['id']] = $url;
        }
        return $out;
    }

    /**
     * Plateformes effectivement renseignées avec leurs métadonnées (utilisé
     * par le ViewModel pour affichage front).
     *
     * @return list<array{id: string, label: string, icon: string, url: string}>
     */
    public function active(): array
    {
        $urls = $this->all();
        $out  = [];
        foreach (self::PLATFORMS as $p) {
            $url = $urls[$p['id']] ?? '';
            if ($url === '') {
                continue;
            }
            $out[] = [
                'id'    => $p['id'],
                'label' => $p['label'],
                'icon'  => $p['icon'],
                'url'   => $url,
            ];
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function save(array $values): void
    {
        $clean = [];
        foreach (self::PLATFORMS as $p) {
            $val = $values[$p['id']] ?? '';
            if (!\is_string($val)) {
                continue;
            }
            $val = trim($val);
            if ($val === '') {
                continue;
            }
            // `mailto:` toléré pour email, sinon on force https-like via esc_url_raw.
            $clean[$p['id']] = (string) esc_url_raw($val, ['http', 'https', 'mailto', 'tel']);
        }
        update_option(self::OPTION, $clean);
    }
}
