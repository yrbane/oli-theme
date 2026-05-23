<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Audite la couverture des traductions du contenu et crée à la demande les
 * traductions manquantes sous forme de brouillons liés.
 *
 * Parcourt tout le contenu traduisible (pages, articles, slides, événements),
 * regroupe par groupe de traduction et signale les langues activées absentes.
 *
 * @package OliTheme\I18n
 *
 * @since 1.1.0
 */
final class TranslationAuditor
{
    /** Types de contenu portant le taxon `language` (hors logs internes). */
    private const TYPES = ['page', 'post', 'oli_slide', 'oli_event'];

    public function __construct(
        private readonly LanguageRegistryInterface $languages,
        private readonly TranslationModelInterface $translations,
    ) {
    }

    /**
     * Contenus auxquels il manque au moins une langue activée. Chaque groupe de
     * traduction n'est listé qu'une fois (ancré sur le premier membre rencontré).
     *
     * @return list<array{post_id: int, title: string, type: string, present: list<string>, missing: list<string>}>
     */
    public function audit(): array
    {
        $enabled = $this->enabledCodes();
        $rows    = [];
        $seen    = [];

        foreach ($this->translatablePosts() as $post) {
            $id = (int) $post->ID;
            if (isset($seen[$id])) {
                continue;
            }

            [$present, $members] = $this->coverageFor($post);
            foreach ($members as $memberId) {
                $seen[$memberId] = true;
            }

            $missing = array_values(array_diff($enabled, $present));
            if ($missing === []) {
                continue;
            }

            $rows[] = [
                'post_id' => $id,
                'title'   => (string) $post->post_title,
                'type'    => (string) $post->post_type,
                'present' => $present,
                'missing' => $missing,
            ];
        }

        return $rows;
    }

    /**
     * Crée un brouillon lié pour chaque langue manquante de chaque contenu,
     * dans le même groupe de traduction. Retourne le nombre de brouillons créés.
     */
    public function installMissingDrafts(): int
    {
        $created = 0;

        foreach ($this->audit() as $row) {
            foreach ($row['missing'] as $lang) {
                $draftId = wp_insert_post([
                    'post_type'    => $row['type'],
                    'post_status'  => 'draft',
                    'post_title'   => \sprintf('[%s] %s', strtoupper($lang), $row['title']),
                    'post_content' => '',
                ], true);

                if (is_wp_error($draftId)) {
                    continue;
                }
                $draftId = (int) $draftId;

                wp_set_object_terms($draftId, $lang, LanguageTaxonomy::NAME, false);
                $this->translations->link($row['post_id'], $draftId);
                ++$created;
            }
        }

        return $created;
    }

    /**
     * Langues présentes et identifiants des membres du groupe d'un post.
     *
     * @param \WP_Post $post
     *
     * @return array{0: list<string>, 1: list<int>}
     */
    private function coverageFor(object $post): array
    {
        $map = $this->translations->getTranslations((int) $post->ID);

        if ($map !== []) {
            return [array_keys($map), array_values($map)];
        }

        // Post isolé (jamais lié) : seul son propre terme de langue est présent.
        $own = $this->ownLanguage($post);

        return [$own !== null ? [$own] : [], [(int) $post->ID]];
    }

    /**
     * @param \WP_Post $post
     */
    private function ownLanguage(object $post): ?string
    {
        /** @var \WP_Term[]|\WP_Error $terms */
        $terms = wp_get_post_terms((int) $post->ID, LanguageTaxonomy::NAME);
        if (!\is_array($terms) || $terms === []) {
            return null;
        }

        return (string) $terms[0]->slug;
    }

    /**
     * @return list<string>
     */
    private function enabledCodes(): array
    {
        return array_map(
            static fn (Language $l): string => $l->code,
            $this->languages->all(),
        );
    }

    /**
     * @return array<int, \WP_Post>
     */
    private function translatablePosts(): array
    {
        /** @var array<int, \WP_Post> $posts */
        $posts = get_posts([
            'post_type'   => self::TYPES,
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
        ]);

        return $posts;
    }
}
