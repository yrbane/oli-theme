<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Contrat du modèle de gestion des groupes de traduction.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
interface TranslationModelInterface
{
    /**
     * Retourne la map des traductions du post (code langue → post ID).
     *
     * @return array<string, int>
     */
    public function getTranslations(int $postId): array;

    /**
     * Lie deux posts dans un même groupe de traduction (réutilise le groupe
     * existant de la source, sinon en crée un).
     */
    public function link(int $sourcePostId, int $targetPostId): void;
}
