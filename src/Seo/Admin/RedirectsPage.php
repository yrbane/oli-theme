<?php

declare(strict_types=1);

namespace OliTheme\Seo\Admin;

use OliTheme\Admin\AdminTabInterface;
use OliTheme\Core\RendererInterface;
use OliTheme\Seo\RedirectModelInterface;

/**
 * Page d'administration des redirections HTTP.
 *
 * Onglet « Redirections » sous Apparence › Oli Theme (themes.php). Les actions de
 * soumission passent par `admin-post.php` avec nonce + capability check.
 *
 * @package OliTheme\Seo\Admin
 *
 * @since 1.0.0
 */
final class RedirectsPage implements AdminTabInterface
{
    public const NONCE_SAVE = 'oli_redirect_save';

    public const NONCE_DELETE = 'oli_redirect_delete';

    public const ACTION_SAVE = 'oli_redirect_save';

    public const ACTION_DELETE = 'oli_redirect_delete';

    public const PER_PAGE = 25;

    /** @var array<int> Codes HTTP autorisés. */
    private const ALLOWED_CODES = [301, 302, 410];

    public function __construct(
        private readonly RedirectModelInterface $redirects,
        private readonly RendererInterface $renderer,
    ) {
    }

    public function id(): string
    {
        return 'redirections';
    }

    public function group(): string
    {
        return 'seo';
    }

    public function label(): string
    {
        return __('Redirections', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    /**
     * Enregistre les handlers admin-post.php (admin_init).
     * Séparé du rendu car admin-post.php ne déclenche pas admin_menu.
     */
    public function registerActions(): void
    {
        add_action('admin_post_' . self::ACTION_SAVE, [$this, 'handleSave']);
        add_action('admin_post_' . self::ACTION_DELETE, [$this, 'handleDelete']);
    }

    public function renderPanel(): void
    {
        $page   = isset($_GET['paged']) && \is_string($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $offset = ($page - 1) * self::PER_PAGE;

        $editId  = isset($_GET['edit']) && \is_string($_GET['edit']) ? (int) $_GET['edit'] : 0;
        $editing = null;

        $allRedirects = $this->redirects->findAll(self::PER_PAGE, $offset);

        if ($editId > 0) {
            foreach ($allRedirects as $candidate) {
                if ($candidate->id === $editId) {
                    $editing = $candidate;
                    break;
                }
            }
        }

        $notice = isset($_GET['notice']) && \is_string($_GET['notice']) ? sanitize_key((string) $_GET['notice']) : '';

        $total      = $this->redirects->count();
        $totalPages = (int) max(1, (int) ceil($total / self::PER_PAGE));

        // Enrichit chaque redirection avec ses URL d'action (nonce par id).
        $rows = [];
        foreach ($allRedirects as $r) {
            $rows[] = [
                'id'         => $r->id,
                'source'     => $r->source,
                'target'     => $r->target,
                'code'       => $r->code,
                'hits'       => $r->hits,
                'edit_url'   => $this->listUrl(['edit' => $r->id]),
                'delete_url' => add_query_arg(
                    [
                        'action'   => self::ACTION_DELETE,
                        'id'       => $r->id,
                        '_wpnonce' => wp_create_nonce(self::ACTION_DELETE . '_' . $r->id),
                    ],
                    admin_url('admin-post.php'),
                ),
            ];
        }

        $editingArray = $editing !== null ? [
            'id'     => $editing->id,
            'source' => $editing->source,
            'target' => $editing->target,
            'code'   => $editing->code,
        ] : null;

        echo $this->renderer->render('admin/redirects.html', [
            'redirects'    => $rows,
            'editing'      => $editingArray,
            'is_editing'   => $editingArray !== null,
            'notice'       => $notice,
            'page'         => $page,
            'total_pages'  => $totalPages,
            'total'        => $total,
            'has_pages'    => $totalPages > 1,
            'prev_page'    => $page > 1 ? $this->listUrl(['paged' => $page - 1]) : '',
            'next_page'    => $page < $totalPages ? $this->listUrl(['paged' => $page + 1]) : '',
            'save_url'     => admin_url('admin-post.php'),
            'nonce_save'   => wp_create_nonce(self::NONCE_SAVE),
            'action_save'  => self::ACTION_SAVE,
            'cancel_url'   => $this->listUrl(),
            'list_empty'   => $rows === [],
        ]);
    }

    /**
     * Handler du formulaire create/edit (admin-post.php?action=oli_redirect_save).
     */
    public function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Accès refusé.', 'oli-theme'), '', ['response' => 403]);
        }

        check_admin_referer(self::NONCE_SAVE);

        /** @var array<string,mixed> $post */
        $post = $_POST;

        $id     = isset($post['id']) ? (int) $post['id'] : 0;
        $source = isset($post['source']) ? sanitize_text_field((string) wp_unslash((string) $post['source'])) : '';
        $target = isset($post['target']) ? esc_url_raw((string) wp_unslash((string) $post['target'])) : '';
        $code   = isset($post['code']) ? (int) $post['code'] : 0;

        $error = $this->validate($source, $target, $code);

        if ($error !== null) {
            $this->redirectToList(['notice' => $error, 'edit' => $id > 0 ? $id : null]);

            return;
        }

        if ($id > 0) {
            $this->redirects->update($id, $source, $target, $code);
        } else {
            $this->redirects->save($source, $target, $code);
        }

        $this->redirectToList(['notice' => $id > 0 ? 'updated' : 'created']);
    }

    /**
     * Handler de suppression (admin-post.php?action=oli_redirect_delete).
     */
    public function handleDelete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Accès refusé.', 'oli-theme'), '', ['response' => 403]);
        }

        $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        check_admin_referer(self::ACTION_DELETE . '_' . $id);

        if ($id <= 0) {
            $this->redirectToList(['notice' => 'invalid']);

            return;
        }

        $this->redirects->delete($id);
        $this->redirectToList(['notice' => 'deleted']);
    }

    /**
     * Valide les champs source/target/code. Retourne un slug d'erreur ou null si OK.
     */
    private function validate(string $source, string $target, int $code): ?string
    {
        if ($source === '' || $source[0] !== '/') {
            return 'invalid_source';
        }

        if (!\in_array($code, self::ALLOWED_CODES, true)) {
            return 'invalid_code';
        }

        if ($code !== 410 && $target === '') {
            return 'missing_target';
        }

        return null;
    }

    /**
     * URL de la liste des redirections (onglet SEO unifié), avec params additionnels.
     *
     * @param array<string, scalar> $extra
     */
    private function listUrl(array $extra = []): string
    {
        return add_query_arg(
            ['page' => 'oli-theme-settings', 'tab' => 'seo', 'sub' => 'redirections'] + $extra,
            admin_url('themes.php'),
        );
    }

    /**
     * Redirige vers la liste avec des paramètres de query string.
     *
     * @param array<string, mixed> $args
     */
    private function redirectToList(array $args): void
    {
        $extra = array_filter($args, static fn ($v) => $v !== null && $v !== '');

        wp_safe_redirect($this->listUrl($extra));
    }
}
