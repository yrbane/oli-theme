<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Page hôte unifiée des réglages du thème (`themes.php?page=oli-theme-settings`).
 *
 * Lit `?tab` (groupe) et `?sub` (sous-onglet), construit la navigation à partir
 * du {@see AdminTabRegistry} et délègue le rendu du panneau actif.
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class ThemeAdminPage
{
    public const PAGE_SLUG = 'oli-theme-settings';

    public function __construct(private readonly AdminTabRegistry $registry)
    {
    }

    public function register(): void
    {
        add_theme_page(
            __('Réglages du thème', 'oli-theme'),
            __('Réglages du thème', 'oli-theme'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
        );
    }

    public function render(): void
    {
        $group = $this->resolveGroup();
        $tab   = $this->resolveTab($group);

        echo '<div class="wrap oli-theme-admin">';
        echo '<h1>' . esc_html(__('Réglages du thème', 'oli-theme')) . '</h1>';

        $this->renderGroupNav($group);

        if ($tab !== null && \count($this->registry->forGroup($group)) > 1) {
            $this->renderSubNav($group, $tab);
        }

        if ($tab === null) {
            echo '</div>';
            return;
        }

        if (!current_user_can($tab->capability())) {
            echo '<p>' . esc_html(__('Accès refusé.', 'oli-theme')) . '</p></div>';
            return;
        }

        echo '<div class="oli-theme-admin__panel">';
        $tab->renderPanel();
        echo '</div></div>';
    }

    private function resolveGroup(): string
    {
        $group = isset($_GET['tab']) && \is_string($_GET['tab'])
            ? sanitize_key((string) $_GET['tab'])
            : AdminGroups::DEFAULT_GROUP;

        if (!AdminGroups::exists($group) || $this->registry->forGroup($group) === []) {
            return AdminGroups::DEFAULT_GROUP;
        }
        return $group;
    }

    private function resolveTab(string $group): ?AdminTabInterface
    {
        $sub = isset($_GET['sub']) && \is_string($_GET['sub'])
            ? sanitize_key((string) $_GET['sub'])
            : '';

        if ($sub !== '') {
            $tab = $this->registry->find($group, $sub);
            if ($tab !== null) {
                return $tab;
            }
        }
        return $this->registry->firstOfGroup($group);
    }

    private function renderGroupNav(string $active): void
    {
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->registry->groupsWithTabs() as $id => $label) {
            $url   = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $id], admin_url('themes.php'));
            $class = 'nav-tab' . ($id === $active ? ' nav-tab-active' : '');
            printf(
                '<a href="%s" class="%s">%s</a>',
                esc_url($url),
                esc_attr($class),
                esc_html($label),
            );
        }
        echo '</h2>';
    }

    private function renderSubNav(string $group, AdminTabInterface $active): void
    {
        // Barre de sous-onglets en flex (et non en float comme `.subsubsub`),
        // afin que le panneau s'affiche bien en dessous, à la ligne suivante.
        echo '<nav class="oli-subnav" style="display:flex;flex-wrap:wrap;gap:0.25rem;'
            . 'margin:1rem 0 1.5rem;border-bottom:1px solid #c3c4c7;">';
        foreach ($this->registry->forGroup($group) as $tab) {
            $url      = add_query_arg(
                ['page' => self::PAGE_SLUG, 'tab' => $group, 'sub' => $tab->id()],
                admin_url('themes.php'),
            );
            $isActive = $tab->id() === $active->id();
            $style    = $isActive
                ? 'border:1px solid #c3c4c7;border-bottom-color:#f0f0f1;background:#f0f0f1;font-weight:600;color:#1d2327;'
                : 'border:1px solid transparent;color:#2271b1;';
            printf(
                '<a href="%s" style="%spadding:0.45rem 0.9rem;margin-bottom:-1px;'
                . 'text-decoration:none;border-radius:3px 3px 0 0;">%s</a>',
                esc_url($url),
                $style,
                esc_html($tab->label()),
            );
        }
        echo '</nav>';
    }
}
