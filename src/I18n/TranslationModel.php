<?php

declare(strict_types=1);

namespace OliTheme\I18n;

use Random\RandomException;

/**
 * Gestion des groupes de traduction.
 *
 * Toutes les versions linguistiques d'un même contenu partagent un identifiant
 * UUID stocké en post meta `_oli_translation_group`. Cela permet de naviguer
 * d'une traduction à l'autre sans table relationnelle dédiée.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class TranslationModel implements TranslationModelInterface
{
    public const META_KEY = '_oli_translation_group';

    /**
     * Récupère l'identifiant du groupe de traduction d'un post.
     */
    public function getGroupId(int $postId): ?string
    {
        /** @var mixed $value */
        $value = get_post_meta($postId, self::META_KEY, true);

        return \is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Force l'identifiant de groupe d'un post (création ou mise à jour).
     */
    public function setGroupId(int $postId, string $groupId): void
    {
        update_post_meta($postId, self::META_KEY, $groupId);
    }

    /**
     * Supprime l'appartenance à un groupe.
     */
    public function unlink(int $postId): void
    {
        delete_post_meta($postId, self::META_KEY);
    }

    /**
     * Génère un nouvel identifiant UUID v4.
     *
     * @throws RandomException Si la source d'entropie échoue.
     */
    public function createGroupId(): string
    {
        $data = random_bytes(16);
        $data[6] = \chr((\ord($data[6]) & 0x0F) | 0x40);
        $data[8] = \chr((\ord($data[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Lie deux posts en réutilisant le groupe du premier (créé si absent).
     *
     * @throws RandomException Si la création d'UUID échoue.
     */
    public function link(int $sourcePostId, int $targetPostId): void
    {
        $groupId = $this->getGroupId($sourcePostId) ?? $this->createGroupId();

        $this->setGroupId($sourcePostId, $groupId);
        $this->setGroupId($targetPostId, $groupId);
    }

    /**
     * Retourne la map des traductions du post (code langue → post ID).
     *
     * @return array<string, int>
     */
    public function getTranslations(int $postId): array
    {
        $groupId = $this->getGroupId($postId);
        if ($groupId === null) {
            return [];
        }

        /** @var array<int, int> $postIds */
        $postIds = get_posts([
            'post_type'   => 'any',
            'numberposts' => -1,
            'post_status' => 'any',
            'fields'      => 'ids',
            'meta_key'    => self::META_KEY,
            'meta_value'  => $groupId,
        ]);

        $map = [];
        foreach ($postIds as $id) {
            /** @var \WP_Term[]|\WP_Error $terms */
            $terms = wp_get_post_terms($id, LanguageTaxonomy::NAME);
            if (!\is_array($terms)) {
                continue;
            }
            foreach ($terms as $term) {
                $map[$term->slug] = (int) $id;
                break;
            }
        }

        return $map;
    }
}
