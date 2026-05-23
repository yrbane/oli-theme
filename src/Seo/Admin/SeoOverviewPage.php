<?php

declare(strict_types=1);

namespace OliTheme\Seo\Admin;

use OliTheme\Admin\AdminTabInterface;
use OliTheme\Core\RendererInterface;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;
use OliTheme\Seo\ScoreCalculatorInterface;
use OliTheme\Seo\SeoMeta;
use OliTheme\Seo\SeoMetaModelInterface;

/**
 * Page d'administration SEO Dashboard.
 *
 * Liste tous les contenus (page, post, oli_event) avec leur score SEO 0-100,
 * leurs longueurs de title/description, leur mot-clé focus et un lien direct
 * vers l'éditeur. Filtres `?type=`, `?min_score=`, `?max_score=`. Export CSV
 * via `admin-post.php?action=oli_seo_export_csv` qui réutilise les filtres.
 *
 * @package OliTheme\Seo\Admin
 *
 * @since 1.0.0
 */
final class SeoOverviewPage implements AdminTabInterface
{
    public const PAGE_SLUG = 'oli-seo-dashboard';

    public const ACTION_EXPORT_CSV = 'oli_seo_export_csv';

    public const PER_PAGE = 25;

    /** @var string[] Types de contenu listés par défaut. */
    private const DEFAULT_TYPES = ['post', 'page', 'oli_event'];

    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly PostModelInterface $posts,
        private readonly SeoMetaModelInterface $metas,
        private readonly ScoreCalculatorInterface $score,
    ) {
    }

    public function id(): string
    {
        return 'dashboard';
    }

    public function group(): string
    {
        return 'seo';
    }

    public function label(): string
    {
        return __('Dashboard', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    /**
     * Hooks admin-post.php (admin_init) — l'export CSV doit être joignable
     * sans passer par admin_menu.
     */
    public function registerActions(): void
    {
        add_action('admin_post_' . self::ACTION_EXPORT_CSV, [$this, 'handleExportCsv']);
    }

    public function renderPanel(): void
    {
        $filters = $this->parseFilters();
        $items   = $this->fetchItems($filters);

        $page       = $filters['paged'];
        $perPage    = self::PER_PAGE;
        $offset     = ($page - 1) * $perPage;
        $totalItems = \count($items);
        $totalPages = (int) max(1, (int) ceil($totalItems / $perPage));

        $paged = \array_slice($items, $offset, $perPage);

        echo $this->renderer->render('admin/seo-overview.html', [
            'title'        => __('SEO Dashboard', 'oli-theme'),
            'items'        => $paged,
            'total'        => $totalItems,
            'page'         => $page,
            'total_pages'  => $totalPages,
            'has_pages'    => $totalPages > 1,
            'prev_page'    => $page > 1 ? $this->buildUrl(['paged' => $page - 1] + $this->filterQuery($filters)) : '',
            'next_page'    => $page < $totalPages ? $this->buildUrl(['paged' => $page + 1] + $this->filterQuery($filters)) : '',
            'filter_type'  => $filters['type'] ?? '',
            'filter_min'   => $filters['min_score'] ?? null,
            'filter_max'   => $filters['max_score'] ?? null,
            'types'        => self::DEFAULT_TYPES,
            'reset_url'    => $this->buildUrl([]),
            'list_empty'   => $paged === [],
            'export_url'   => add_query_arg(
                ['action' => self::ACTION_EXPORT_CSV] + $this->filterQuery($filters),
                admin_url('admin-post.php'),
            ),
        ]);
    }

    /**
     * Génère un téléchargement CSV des items avec les filtres courants.
     */
    public function handleExportCsv(): void
    {
        if (\function_exists('current_user_can') && !current_user_can('manage_options')) {
            wp_die(__('Accès refusé.', 'oli-theme'), '', ['response' => 403]);
        }

        $filters = $this->parseFilters();
        $items   = $this->fetchItems($filters);

        if (\function_exists('nocache_headers')) {
            nocache_headers();
        }
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="oli-seo-dashboard.csv"');
        }

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        // BOM UTF-8 pour Excel.
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['ID', 'Type', 'Statut', 'Titre', 'Score', 'Mot-clé', 'Longueur titre', 'Longueur description', 'Édition'], ',', '"', '\\');

        foreach ($items as $item) {
            fputcsv($out, [
                $item['id'],
                $item['type'],
                $item['status'],
                $item['title'],
                $item['score'],
                $item['focus_keyword'],
                $item['title_length'],
                $item['description_length'],
                $item['edit_url'],
            ], ',', '"', '\\');
        }

        fclose($out);
    }

    /**
     * Récupère les items enrichis (item = ligne du tableau de bord).
     *
     * @param array{type:?string, min_score:?int, max_score:?int, paged:int} $filters
     *
     * @return list<array<string, mixed>>
     */
    private function fetchItems(array $filters): array
    {
        $types = $filters['type'] !== null ? [$filters['type']] : self::DEFAULT_TYPES;

        /** @var int[] $ids */
        $ids = get_posts([
            'post_type'      => $types,
            'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $items = [];
        foreach ($ids as $id) {
            $post = $this->posts->find((int) $id);
            if ($post === null) {
                continue;
            }

            $meta  = $this->metas->find($post->id);
            $score = $this->score->calculate($meta, $post);

            if ($filters['min_score'] !== null && $score < $filters['min_score']) {
                continue;
            }
            if ($filters['max_score'] !== null && $score > $filters['max_score']) {
                continue;
            }

            $items[] = $this->itemFromPost($post, $meta, $score);
        }

        return $items;
    }

    /**
     * Transforme post + meta + score en ligne pour le tableau / CSV.
     *
     * @return array<string, mixed>
     */
    private function itemFromPost(PostEntity $post, SeoMeta $meta, int $score): array
    {
        $title       = $meta->title ?? $post->title;
        $description = $meta->description ?? '';

        return [
            'id'                  => $post->id,
            'type'                => $post->type,
            'status'              => \function_exists('get_post_status') ? (string) get_post_status($post->id) : 'publish',
            'title'               => $title,
            'score'               => $score,
            'score_class'         => $this->scoreClass($score),
            'focus_keyword'       => $meta->focusKeyword ?? '',
            'title_length'        => mb_strlen($title),
            'description_length'  => mb_strlen($description),
            'edit_url'            => \function_exists('get_edit_post_link') ? (string) get_edit_post_link($post->id, 'raw') : '',
        ];
    }

    /**
     * Slug CSS pour colorer la pastille de score.
     */
    private function scoreClass(int $score): string
    {
        if ($score >= 70) {
            return 'good';
        }
        if ($score >= 40) {
            return 'medium';
        }
        return 'bad';
    }

    /**
     * Lit et normalise les filtres depuis $_GET.
     *
     * @return array{type:?string, min_score:?int, max_score:?int, paged:int}
     */
    private function parseFilters(): array
    {
        $type = isset($_GET['type']) && \is_string($_GET['type']) ? sanitize_key((string) $_GET['type']) : '';
        $min  = isset($_GET['min_score']) && \is_string($_GET['min_score']) && $_GET['min_score'] !== '' ? (int) $_GET['min_score'] : null;
        $max  = isset($_GET['max_score']) && \is_string($_GET['max_score']) && $_GET['max_score'] !== '' ? (int) $_GET['max_score'] : null;
        $page = isset($_GET['paged']) && \is_string($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;

        return [
            'type'      => \in_array($type, self::DEFAULT_TYPES, true) ? $type : null,
            'min_score' => $min !== null ? max(0, min(100, $min)) : null,
            'max_score' => $max !== null ? max(0, min(100, $max)) : null,
            'paged'     => $page,
        ];
    }

    /**
     * Restreint les filtres au sous-ensemble présent (utile pour reconstruire des URL propres).
     *
     * @param array{type:?string, min_score:?int, max_score:?int, paged:int} $filters
     *
     * @return array<string, scalar>
     */
    private function filterQuery(array $filters): array
    {
        $q = [];
        if ($filters['type'] !== null) {
            $q['type'] = $filters['type'];
        }
        if ($filters['min_score'] !== null) {
            $q['min_score'] = $filters['min_score'];
        }
        if ($filters['max_score'] !== null) {
            $q['max_score'] = $filters['max_score'];
        }
        return $q;
    }

    /**
     * Construit une URL absolue de la page admin avec les paramètres donnés.
     *
     * @param array<string, scalar> $args
     */
    private function buildUrl(array $args): string
    {
        return add_query_arg(['page' => self::PAGE_SLUG] + $args, admin_url('tools.php'));
    }
}
