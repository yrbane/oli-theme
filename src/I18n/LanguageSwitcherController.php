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
        $current      = $this->resolver->current();
        $default      = $this->registry->default();
        $translations = $currentPostId > 0
            ? $this->translations->getTranslations($currentPostId)
            : [];

        $items = [];
        foreach ($this->registry->all() as $language) {
            $hasTranslation = isset($translations[$language->code]);

            if ($hasTranslation) {
                // get_permalink passe par notre filtre et préfixe avec la langue
                // active ; on relocalise vers la langue cible pour le switcher.
                $url = $this->relocateUrl(
                    (string) get_permalink($translations[$language->code]),
                    $current,
                    $language,
                    $default,
                );
            } else {
                $url = $language->equals($default)
                    ? (string) home_url('/')
                    : (string) home_url('/' . $language->code . '/');
            }

            $items[] = new LanguageSwitcherItem(
                code: $language->code,
                label: $language->label,
                nativeLabel: $language->nativeLabel,
                flag: $language->flag,
                url: $url,
                isCurrent: $language->equals($current),
                hasTranslation: $hasTranslation,
            );
        }

        return new LanguageSwitcherViewModel($current, $items);
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
