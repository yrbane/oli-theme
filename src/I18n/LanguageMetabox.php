<?php

declare(strict_types=1);

namespace OliTheme\I18n;

use OliTheme\Core\RendererInterface;

/**
 * Metabox 'Traductions' affichée sur l'écran d'édition des posts/pages.
 *
 * Permet de consulter ou définir manuellement le groupe de traduction d'un contenu.
 * UI rendue via Lunar (template admin/language-metabox.html.tpl, créé en Plan 3).
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class LanguageMetabox
{
    private const NONCE_ACTION = 'oli_lang_metabox';
    private const NONCE_FIELD = '_oli_lang_nonce';
    private const FIELD = 'oli_translation_group';

    public function __construct(
        private readonly LanguageRegistry $registry,
        private readonly TranslationModel $translations,
        private readonly RendererInterface $renderer,
    ) {
    }

    /**
     * À brancher sur 'add_meta_boxes'.
     */
    public function register(): void
    {
        foreach (['post', 'page'] as $screen) {
            add_meta_box(
                'oli-language-translations',
                'Traductions',
                [$this, 'render'],
                $screen,
                'side',
            );
        }
    }

    /**
     * Rendu de la metabox.
     */
    public function render(\WP_Post $post): void
    {
        $groupId      = $this->translations->getGroupId($post->ID) ?? '';
        $translations = $this->translations->getTranslations($post->ID);

        // On pré-construit la liste des liens : Lunar Template ne compile pas
        // correctement les accès indexés dynamiques (`map[obj.attr]`) dans les
        // templates ; on évite donc le lookup côté template.
        $entries = [];
        foreach ($this->registry->all() as $language) {
            if (!isset($translations[$language->code])) {
                continue;
            }
            $entries[] = [
                'code'   => $language->code,
                'label'  => $language->label,
                'flag'   => $language->flag,
                'postId' => (int) $translations[$language->code],
            ];
        }

        echo $this->renderer->render('admin/language-metabox.html', [
            'entries'    => $entries,
            'hasEntries' => $entries !== [],
            'groupId'    => $groupId,
            'nonce'      => wp_create_nonce(self::NONCE_ACTION),
            'nonceField' => self::NONCE_FIELD,
            'field'      => self::FIELD,
        ]);
    }

    /**
     * À brancher sur 'save_post'.
     *
     * @param array<string, mixed> $postData Source des données ($_POST par défaut).
     */
    public function save(int $postId, array $postData): void
    {
        if (!isset($postData[self::NONCE_FIELD]) || !\is_string($postData[self::NONCE_FIELD])) {
            return;
        }

        if (!wp_verify_nonce($postData[self::NONCE_FIELD], self::NONCE_ACTION)) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $value = $postData[self::FIELD] ?? '';
        if (\is_string($value) && $value !== '') {
            $this->translations->setGroupId($postId, $value);
        }
    }
}
