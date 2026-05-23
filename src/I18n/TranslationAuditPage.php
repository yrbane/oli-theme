<?php

declare(strict_types=1);

namespace OliTheme\I18n;

use OliTheme\Admin\AdminTabInterface;
use OliTheme\Settings\ThemeSettingsPage;

/**
 * Onglet « Langues » : réglages des langues (formulaire Settings API) suivis de
 * l'audit de couverture des traductions et de la création de brouillons.
 *
 * @package OliTheme\I18n
 *
 * @since 1.1.0
 */
final class TranslationAuditPage implements AdminTabInterface
{
    public function __construct(
        private readonly TranslationAuditor $auditor,
        private readonly ThemeSettingsPage $settings,
    ) {
    }

    public function id(): string
    {
        return 'languages';
    }

    public function group(): string
    {
        return 'identite';
    }

    public function label(): string
    {
        return __('Langues', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        if (!empty($_POST['oli_translation_create_drafts'])) {
            $this->handleCreateDrafts();
        }

        // 1. Réglages des langues (langues activées, défaut, repli).
        $this->settings->renderPanelFor('languages');

        // 2. Audit de couverture des traductions.
        echo '<hr style="margin:2rem 0 1rem;">';
        $rows = $this->auditor->audit();
        ?>
        <h2 class="title" style="margin-top:0.5rem;"><?php esc_html_e('Couverture des traductions', 'oli-theme'); ?></h2>
        <p class="description">
            <?php esc_html_e('Vérifie que chaque contenu (pages, articles, slides, événements) possède une version dans chaque langue activée.', 'oli-theme'); ?>
        </p>

        <?php if ($rows === []): ?>
            <p style="color:#007017;font-weight:600;">&#10004; <?php esc_html_e('Tout le contenu est entièrement traduit.', 'oli-theme'); ?></p>
        <?php else: ?>
            <p>
                <?php
                printf(
                    /* translators: %d: nombre de contenus incomplets */
                    esc_html(_n('%d contenu auquel il manque au moins une traduction :', '%d contenus auxquels il manque au moins une traduction :', \count($rows), 'oli-theme')),
                    (int) \count($rows),
                );
            ?>
            </p>
            <table class="widefat striped" style="max-width:820px;margin:0.5rem 0 1rem;">
                <thead><tr>
                    <th><?php esc_html_e('Contenu', 'oli-theme'); ?></th>
                    <th><?php esc_html_e('Type', 'oli-theme'); ?></th>
                    <th><?php esc_html_e('Langues présentes', 'oli-theme'); ?></th>
                    <th><?php esc_html_e('Langues manquantes', 'oli-theme'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <?php $link = get_edit_post_link($row['post_id']); ?>
                            <?php if (\is_string($link) && $link !== ''): ?>
                                <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($row['title']); ?></a>
                            <?php else: ?>
                                <?php echo esc_html($row['title']); ?>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($row['type']); ?></code></td>
                        <td><?php echo esc_html(strtoupper(implode(', ', $row['present']))); ?></td>
                        <td>
                            <?php foreach ($row['missing'] as $lang): ?>
                                <span style="display:inline-block;background:#fcf0f1;color:#b32d2e;border:1px solid #f0c5c8;border-radius:3px;padding:0 0.4rem;margin:0 0.15rem;font-weight:600;"><?php echo esc_html(strtoupper($lang)); ?></span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <form method="post" action="" style="margin:0;">
                <?php wp_nonce_field('oli_translation_create_drafts', '_oli_translation_nonce'); ?>
                <input type="hidden" name="oli_translation_create_drafts" value="1">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Créer les brouillons de traduction manquants', 'oli-theme'); ?>
                </button>
                <span class="description" style="margin-left:0.5rem;">
                    <?php esc_html_e('Crée un brouillon lié (même groupe de traduction, titre préfixé) par langue manquante, à compléter ensuite.', 'oli-theme'); ?>
                </span>
            </form>
        <?php endif; ?>
        <?php
    }

    private function handleCreateDrafts(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('oli_translation_create_drafts', '_oli_translation_nonce');

        $created = $this->auditor->installMissingDrafts();

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(\sprintf(
                /* translators: %d: nombre de brouillons créés */
                _n('%d brouillon de traduction créé.', '%d brouillons de traduction créés.', $created, 'oli-theme'),
                $created,
            )),
        );
    }
}
