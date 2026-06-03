<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationModelInterface;

/**
 * Construit les balises hreflang pour le SEO multilingue.
 *
 * Retourne un tableau ordonné d'entrées `['code' => ..., 'url' => ...]`
 * incluant toutes les traductions disponibles et l'entrée `x-default`.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class HreflangBuilder
{
    /**
     * @param LanguageRegistryInterface $registry Registre des langues activées.
     * @param TranslationModelInterface $translation Modèle des groupes de traduction.
     */
    public function __construct(
        private readonly LanguageRegistryInterface $registry,
        private readonly TranslationModelInterface $translation,
    ) {
    }

    /**
     * Construit la liste des entrées hreflang pour le post donné.
     *
     * @param int $postId Identifiant WordPress du post.
     *
     * @return array<int, array{code: string, url: string}>
     */
    public function build(int $postId): array
    {
        $translations = $this->translation->getTranslations($postId);
        $entries = [];
        $defaultUrl = null;
        $default = $this->registry->default();
        $defaultCode = $default->code;

        foreach ($this->registry->all() as $language) {
            if (!\array_key_exists($language->code, $translations)) {
                continue;
            }

            $translatedPostId = (int) $translations[$language->code];

            // Si la traduction cible est la page d'accueil (`page_on_front`)
            // de sa langue, on pointe vers la home racine pour éviter qu'un
            // permalien /accueil/ ou /en/home/ ne se substitue à la home SEO.
            if ($this->isFrontPageInLanguage($translatedPostId, $language, $default)) {
                $url = $this->homeUrlForLanguage($language, $default);
            } else {
                $permalink = get_permalink($translatedPostId);
                $url = \is_string($permalink) ? $this->stripActiveLangPrefix($permalink, $default) : '';
                if (!$language->equals($default) && !str_contains($url, '/' . $language->code . '/')) {
                    $url = $this->injectLangPrefix($url, $language->code);
                }
            }

            $entries[] = ['code' => $language->code, 'url' => $url];

            if ($language->code === $defaultCode) {
                $defaultUrl = $url;
            }
        }

        if ($defaultUrl !== null) {
            $entries[] = ['code' => 'x-default', 'url' => $defaultUrl];
        }

        return $entries;
    }

    /**
     * Retire le préfixe `/<code>/` de toute langue non-défaut au début du path.
     * Utile pour neutraliser le préfixe ajouté par {@see \OliTheme\I18n\LanguageUrlFilter}
     * sur les permaliens, quand on calcule des URL pour une langue cible.
     */
    private function stripActiveLangPrefix(string $url, Language $default): string
    {
        foreach ($this->registry->all() as $lang) {
            if ($lang->equals($default)) {
                continue;
            }
            $url = preg_replace('~(://[^/]+)/' . preg_quote($lang->code, '~') . '/~', '$1/', $url, 1) ?? $url;
        }

        return $url;
    }

    /**
     * Insère le préfixe `/<code>/` juste après le host dans une URL absolue.
     */
    private function injectLangPrefix(string $url, string $code): string
    {
        return preg_replace('~^(https?://[^/]+)/~', '$1/' . $code . '/', $url, 1) ?? $url;
    }

    /**
     * Indique si `$postId` est la page d'accueil (`page_on_front`) de la langue cible.
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

        $frontTranslations = $this->translation->getTranslations($front);

        return isset($frontTranslations[$target->code])
            && (int) $frontTranslations[$target->code] === $postId;
    }

    /**
     * URL racine d'une langue cible, indépendamment de la langue active.
     */
    private function homeUrlForLanguage(Language $target, Language $default): string
    {
        $base = $this->siteRootUrl($default);

        return $target->equals($default) ? $base : $base . $target->code . '/';
    }

    /**
     * URL racine du site, sans préfixe de langue active.
     */
    private function siteRootUrl(Language $default): string
    {
        $url = (string) home_url('/');
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
}
