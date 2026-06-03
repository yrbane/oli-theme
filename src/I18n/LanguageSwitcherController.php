<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Construit le ViewModel du switcher de langue.
 *
 * Pour chaque langue activée, cherche la traduction du contenu courant via
 * {@see TranslationModel}; en l'absence de traduction, l'URL de l'item pointe
 * vers la home de la langue cible.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class LanguageSwitcherController implements LanguageSwitcherControllerInterface
{
    public function __construct(
        private readonly LanguageRegistry $registry,
        private readonly LanguageResolver $resolver,
        private readonly TranslationModel $translations,
    ) {
    }

    /**
     * Construit le ViewModel pour le post courant (0 = pas de post, ex. archive).
     */
    public function build(int $currentPostId): LanguageSwitcherViewModel
    {
        $current = $this->resolver->current();

        // Une seule langue activée → aucun sélecteur (pas de drapeau du tout).
        if (\count($this->registry->all()) <= 1) {
            return new LanguageSwitcherViewModel($current, []);
        }

        $default      = $this->registry->default();
        $translations = $currentPostId > 0
            ? $this->translations->getTranslations($currentPostId)
            : [];

        $items = [];
        foreach ($this->registry->all() as $language) {
            $hasTranslation = isset($translations[$language->code]);

            if ($hasTranslation) {
                $translationId = (int) $translations[$language->code];

                // Cas spécial : si la traduction cible est la page d'accueil
                // (page_on_front) de sa langue, on pointe vers la home racine
                // de cette langue plutôt que vers le permalien /accueil/.
                if ($this->isFrontPageInLanguage($translationId, $language, $default)) {
                    $url = $this->homeUrlForLanguage($language, $default);
                } else {
                    // get_permalink passe par notre filtre et préfixe avec la langue
                    // active ; on relocalise vers la langue cible pour le switcher.
                    $url = $this->relocateUrl(
                        (string) get_permalink($translationId),
                        $current,
                        $language,
                        $default,
                    );
                }
            } else {
                $url = $this->homeUrlForLanguage($language, $default);
            }

            $items[] = new LanguageSwitcherItem(
                code: $language->code,
                label: $language->label,
                nativeLabel: $language->nativeLabel,
                flag: $language->flag,
                url: $url,
                isCurrent: $language->equals($current),
                hasTranslation: $hasTranslation,
                flagUrl: $this->flagUrlFor($language->code),
            );
        }

        return new LanguageSwitcherViewModel($current, $items);
    }

    /**
     * URL absolue vers un drapeau SVG/PNG si présent dans
     * `assets/img/flags/{code}.{svg|png}`. Retourne null sinon
     * (le template tombera alors sur l'emoji `flag`).
     */
    private function flagUrlFor(string $code): ?string
    {
        if (!\function_exists('get_template_directory')) {
            return null;
        }

        $themePath = (string) get_template_directory();
        foreach (['svg', 'png'] as $ext) {
            $relative = 'assets/img/flags/' . $code . '.' . $ext;
            if (is_file($themePath . '/' . $relative)) {
                $themeUri = \function_exists('get_template_directory_uri')
                    ? (string) get_template_directory_uri()
                    : '';

                return $themeUri . '/' . $relative;
            }
        }

        return null;
    }

    /**
     * URL racine d'une langue cible, indépendamment de la langue ACTIVE.
     *
     * `home_url()` est filtré par {@see LanguageUrlFilter} pour préfixer la
     * langue active : sur /en/, `home_url('/it/')` retourne `/en/it/`. Ici
     * on construit l'URL pour la langue *cible* du switcher, donc on strippe
     * d'abord tout préfixe de langue (autre que le défaut) dans la base, puis
     * on rajoute le préfixe de la cible si non-défaut.
     */
    private function homeUrlForLanguage(Language $target, Language $default): string
    {
        $base = $this->siteRootUrl();

        return $target->equals($default)
            ? $base
            : $base . $target->code . '/';
    }

    /**
     * URL racine du site, sans préfixe de langue active.
     */
    private function siteRootUrl(): string
    {
        $url = (string) home_url('/');
        $default = $this->registry->default();
        foreach ($this->registry->all() as $lang) {
            if ($lang->equals($default)) {
                continue;
            }
            $prefix = '/' . $lang->code . '/';
            if (str_ends_with($url, $prefix)) {
                return substr($url, 0, -\strlen($prefix)) . '/';
            }
        }

        return $url;
    }

    /**
     * Indique si `$postId` est la page d'accueil (`page_on_front`) de la langue
     * `$target`. Pour le défaut, comparaison directe ; sinon on regarde la
     * traduction de `page_on_front` dans la langue cible.
     */
    private function isFrontPageInLanguage(int $postId, Language $target, Language $default): bool
    {
        if ((string) get_option('show_on_front') !== 'page') {
            return false;
        }
        $front = (int) get_option('page_on_front', 0);
        if ($front <= 0) {
            return false;
        }
        if ($target->equals($default)) {
            return $postId === $front;
        }

        $frontTranslations = $this->translations->getTranslations($front);

        return isset($frontTranslations[$target->code])
            && (int) $frontTranslations[$target->code] === $postId;
    }

    /**
     * Re-préfixe une URL pour qu'elle pointe vers la langue cible, en retirant
     * d'abord le préfixe de la langue active s'il a été ajouté par les filtres.
     * Garantit que `get_permalink()` côté switcher cible bien l'autre langue.
     */
    private function relocateUrl(string $url, Language $current, Language $target, Language $default): string
    {
        // 1. Retire le préfixe de la langue active si présent et différent de la cible.
        if (!$current->equals($target) && !$current->equals($default)) {
            $activePrefix = '/' . $current->code . '/';
            $pos          = strpos($url, $activePrefix);
            if ($pos !== false) {
                $url = substr_replace($url, '/', $pos, \strlen($activePrefix));
            }
        }

        // 2. Ajoute le préfixe de la cible (sauf si cible = défaut).
        if (!$target->equals($default)) {
            $targetPrefix = '/' . $target->code . '/';
            if (!str_contains($url, $targetPrefix)) {
                $url = preg_replace('~^(https?://[^/]+)/~', '$1' . $targetPrefix, $url, 1) ?? $url;
            }
        }

        return $url;
    }
}
