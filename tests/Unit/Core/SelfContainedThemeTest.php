<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Garantit que le thème reste auto-suffisant — aucune dépendance à un plugin
 * WordPress tiers (ACF, Polylang, WPML, Yoast, WooCommerce, Rank Math, etc.).
 *
 * Toute la logique multilingue, SEO, galerie, réservations etc. doit vivre
 * dans `src/`. Si un appel à une fonction/classe de plugin tiers apparaît, le
 * test échoue : c'est volontaire. Si une nouvelle intégration légitime est
 * ajoutée (rare), il faut soit la sortir du runtime (ex. mu-plugin externe au
 * thème), soit ajouter une exception explicite et documentée dans
 * `ALLOWED_EXCEPTIONS`.
 *
 * @package OliTheme\Tests\Unit\Core
 *
 * @since 1.0.0
 */
final class SelfContainedThemeTest extends TestCase
{
    /**
     * Signatures de plugins tiers à détecter. Chaque entrée : libellé du
     * plugin → liste de regex à matcher contre le contenu des fichiers PHP.
     *
     * @var array<string, list<string>>
     */
    private const FORBIDDEN_SIGNATURES = [
        'Advanced Custom Fields (ACF)' => [
            '~\b(get_field|the_field|have_rows|the_row|update_field|delete_field|get_sub_field|the_sub_field|get_field_object|acf_add_local_field_group)\s*\(~',
            '~\bclass_exists\([\'"]ACF[\'"]~',
            '~\bACF\\\\~',
        ],
        'Polylang' => [
            '~\bpll_(current_language|the_languages|register_string|translate_string|get_post|get_term|languages_list|home_url|default_language)\s*\(~',
            '~\bclass_exists\([\'"]Polylang[\'"]~',
        ],
        'WPML' => [
            '~\bicl_(object_id|register_string|t|translate)\s*\(~',
            '~\bwpml_(get_current_language|get_active_languages|object_id)\b~',
            '~\bSITEPRESS_VERSION\b~',
            '~apply_filters\(\s*[\'"]wpml_~',
        ],
        'Yoast SEO' => [
            '~\bclass_exists\([\'"]WPSEO[\'"]~',
            '~\bWPSEO_(Options|Meta|Frontend|Sitemaps|Utils)\b~',
            '~\bYoastSEO\s*\(~',
        ],
        'Rank Math' => [
            '~\bclass_exists\([\'"]RankMath[\'"]~',
            '~\bRankMath\\\\~',
        ],
        'WooCommerce' => [
            '~\bclass_exists\([\'"]WooCommerce[\'"]~',
            '~\bWC\s*\(\)~',
            '~\bwc_(get_product|get_order|get_orders|add_to_cart|get_cart)\s*\(~',
            '~\bis_woocommerce\s*\(~',
        ],
        'The Events Calendar' => [
            '~\bclass_exists\([\'"]Tribe__Events[\'"]~',
            '~\btribe_(get_events|is_event|get_venue)\s*\(~',
        ],
        'Contact Form 7' => [
            '~\bclass_exists\([\'"]WPCF7[\'"]~',
            '~\bwpcf7_(contact_form|add_form_tag)\s*\(~',
        ],
        'Elementor' => [
            '~\bclass_exists\([\'"]Elementor\\\\Plugin[\'"]~',
            '~\bElementor\\\\Plugin::instance~',
        ],
        'Gravity Forms' => [
            '~\bclass_exists\([\'"]GFForms[\'"]~',
            '~\bGFAPI::~',
        ],
    ];

    /**
     * Fichiers/répertoires à exclure du scan (rien actuellement : tout `src/`
     * doit être propre).
     *
     * @var list<string>
     */
    private const EXCLUDED_PATHS = [];

    public function testNoThirdPartyPluginDependencyInSrc(): void
    {
        $srcDir = \dirname(__DIR__, 3) . '/src';
        $offenders = [];

        foreach ($this->phpFiles($srcDir) as $file) {
            $relativePath = (string) ltrim(str_replace($srcDir, '', (string) $file->getRealPath()), '/');
            if ($this->isExcluded($relativePath)) {
                continue;
            }

            $content = (string) file_get_contents((string) $file->getRealPath());

            foreach (self::FORBIDDEN_SIGNATURES as $plugin => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $content, $m) === 1) {
                        $offenders[] = sprintf(
                            '%s : signature « %s » détectée (%s)',
                            $relativePath,
                            $plugin,
                            trim((string) ($m[0] ?? '')),
                        );
                    }
                }
            }
        }

        self::assertSame(
            [],
            $offenders,
            "Le thème doit rester auto-suffisant — aucune dépendance à un plugin tiers détectée dans src/.\n"
            . "Si une intégration légitime est ajoutée, ajouter une exception explicite à ALLOWED_EXCEPTIONS.\n"
            . "Détections :\n - " . implode("\n - ", $offenders),
        );
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $dir): iterable
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $file) {
            \assert($file instanceof SplFileInfo);
            if ($file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    private function isExcluded(string $relativePath): bool
    {
        foreach (self::EXCLUDED_PATHS as $excluded) {
            if (str_starts_with($relativePath, $excluded)) {
                return true;
            }
        }

        return false;
    }
}
