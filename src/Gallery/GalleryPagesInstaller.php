<?php

declare(strict_types=1);

namespace OliTheme\Gallery;

use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationModelInterface;

/**
 * Vérifie et installe les pages WordPress nécessaires aux galeries.
 *
 * Le routing galerie ({@see \OliTheme\Posts\PageController}) repose sur des
 * slugs figés par langue : `photos`/`videos` (fr) et `photos-en`/`videos-en`
 * (en). Ce service détecte les pages manquantes et peut les créer (publiées,
 * avec le terme de langue et la liaison de traduction FR↔EN).
 *
 * @package OliTheme\Gallery
 *
 * @since 1.1.0
 */
final class GalleryPagesInstaller
{
    /**
     * Définition figée des pages galerie, par type et langue.
     *
     * @var list<array{kind: string, lang: string, slug: string, title: string}>
     */
    private const DEFS = [
        ['kind' => 'photos', 'lang' => 'fr', 'slug' => 'photos',     'title' => 'Photos'],
        ['kind' => 'videos', 'lang' => 'fr', 'slug' => 'videos',     'title' => 'Vidéos'],
        ['kind' => 'photos', 'lang' => 'en', 'slug' => 'photos-en',  'title' => 'Photos'],
        ['kind' => 'videos', 'lang' => 'en', 'slug' => 'videos-en',  'title' => 'Videos'],
    ];

    public function __construct(
        private readonly LanguageRegistryInterface $languages,
        private readonly TranslationModelInterface $translations,
    ) {
    }

    /**
     * Pages attendues, restreintes aux langues activées prises en charge (fr, en).
     *
     * @return list<array{kind: string, lang: string, slug: string, title: string}>
     */
    public function expected(): array
    {
        return array_values(array_filter(
            self::DEFS,
            fn (array $def): bool => $this->languages->isEnabled($def['lang']),
        ));
    }

    /**
     * État de chaque page attendue.
     *
     * @return list<array{kind: string, lang: string, slug: string, title: string, exists: bool, page_id: int|null}>
     */
    public function status(): array
    {
        $rows = [];
        foreach ($this->expected() as $def) {
            $post           = $this->findPage($def['slug']);
            $def['exists']  = $post !== null && $post->post_status === 'publish';
            $def['page_id'] = $post !== null ? (int) $post->ID : null;
            $rows[]         = $def;
        }

        return $rows;
    }

    /**
     * Crée (ou publie) les pages manquantes, pose le terme de langue et lie les
     * traductions FR↔EN. Retourne le nombre de pages effectivement créées ou
     * publiées.
     */
    public function installMissing(): int
    {
        $created    = 0;
        $idsBySlug  = [];

        foreach ($this->expected() as $def) {
            $post = $this->findPage($def['slug']);

            if ($post === null) {
                $id = wp_insert_post([
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                    'post_title'   => $def['title'],
                    'post_name'    => $def['slug'],
                    'post_content' => '',
                ], true);

                if (is_wp_error($id)) {
                    continue;
                }
                $id = (int) $id;
                ++$created;
            } else {
                $id = (int) $post->ID;
                if ($post->post_status !== 'publish') {
                    wp_update_post(['ID' => $id, 'post_status' => 'publish']);
                    ++$created;
                }
            }

            wp_set_object_terms($id, $def['lang'], 'language', false);
            $idsBySlug[$def['slug']] = $id;
        }

        $this->linkTranslations($idsBySlug);

        return $created;
    }

    /**
     * Lie les paires FR↔EN dans un même groupe de traduction si les deux pages
     * sont présentes.
     *
     * @param array<string, int> $idsBySlug
     */
    private function linkTranslations(array $idsBySlug): void
    {
        $pairs = [['photos', 'photos-en'], ['videos', 'videos-en']];
        foreach ($pairs as [$fr, $en]) {
            if (isset($idsBySlug[$fr], $idsBySlug[$en])) {
                $this->translations->link($idsBySlug[$fr], $idsBySlug[$en]);
            }
        }
    }

    /**
     * Retourne la page d'un slug donné (tout statut), ou null.
     *
     * @return \WP_Post|null Objet `WP_Post` au runtime (ou stdClass en test).
     */
    private function findPage(string $slug): ?object
    {
        /** @var array<int, \WP_Post> $pages */
        $pages = get_posts([
            'name'        => $slug,
            'post_type'   => 'page',
            'post_status' => 'any',
            'numberposts' => 1,
        ]);

        return $pages[0] ?? null;
    }
}
