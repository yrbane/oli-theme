<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

use OliTheme\I18n\Language;

/**
 * Construit les arbres de menus (primaire + pied de page) pour une langue donnée.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final class MenuController implements MenuControllerInterface
{
    /**
     * @param MenuLocations $locations Gestionnaire des locations de menus par langue
     * @param MenuModelInterface $model Modèle de conversion liste plate → arbre
     */
    public function __construct(
        private readonly MenuLocations $locations,
        private readonly MenuModelInterface $model,
    ) {
    }

    /**
     * Construit l'arbre du menu principal pour une langue donnée.
     *
     * @param Language $language Langue cible
     *
     * @return MenuItemEntity[]
     */
    public function buildPrimary(Language $language): array
    {
        return $this->buildFor($this->locations->primaryFor($language));
    }

    /**
     * Construit l'arbre du menu pied de page pour une langue donnée.
     *
     * @param Language $language Langue cible
     *
     * @return MenuItemEntity[]
     */
    public function buildFooter(Language $language): array
    {
        return $this->buildFor($this->locations->footerFor($language));
    }

    /**
     * Récupère et convertit les items d'une location de menu.
     *
     * Résout d'abord la location en menu ID via `get_nav_menu_locations()` :
     * `wp_get_nav_menu_items()` attend un identifiant de menu (ID, slug, name
     * ou WP_Term), pas une `theme_location` (issue #5). Retourne un tableau
     * vide si aucun menu n'est assigné à la location.
     *
     * @param string $location Clé de location WordPress
     *
     * @return MenuItemEntity[]
     */
    private function buildFor(string $location): array
    {
        if (! has_nav_menu($location)) {
            return [];
        }

        $menuLocations = get_nav_menu_locations();
        if (! \is_array($menuLocations) || ! isset($menuLocations[$location])) {
            return [];
        }

        $items = wp_get_nav_menu_items((int) $menuLocations[$location]);
        if (! \is_array($items)) {
            return [];
        }

        $currentObjectId = (int) get_queried_object_id();
        $currentUrlPath  = $this->currentUrlPath();

        return $this->model->toTree($items, $currentObjectId, $currentUrlPath);
    }

    /**
     * Path normalisé de l'URL courante (sans query, sans trailing slash). Permet
     * à `MenuModel` de marquer comme « courant » l'item qui pointe vers une
     * archive de CPT (où `get_queried_object_id()` vaut 0).
     */
    private function currentUrlPath(): ?string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? null;
        if (!\is_string($uri) || $uri === '') {
            return null;
        }

        $path = parse_url($uri, \PHP_URL_PATH);

        return \is_string($path) ? $path : null;
    }
}
