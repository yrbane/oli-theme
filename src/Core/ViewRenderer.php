<?php

declare(strict_types=1);

namespace OliTheme\Core;

use Lunar\Template\Renderer\TemplateRenderer;

/**
 * Wrapper du moteur Lunar Template Engine pour le thème oli-theme.
 *
 * Centralise la configuration du moteur (chemin templates, cache),
 * expose les variables globales injectées dans chaque rendu (siteName,
 * lang, i18n...) et fournit des macros utilitaires (asset, formatDate...).
 *
 * Les templates portent l'extension .html.tpl et sont stockés dans
 * templates/ à la racine du thème.
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
final class ViewRenderer
{
    private TemplateRenderer $engine;

    /**
     * @param string $templatesPath Chemin absolu vers le dossier templates/.
     * @param string $cachePath Chemin absolu vers le dossier de cache compilé.
     */
    public function __construct(string $templatesPath, string $cachePath)
    {
        $this->engine = new TemplateRenderer(
            templatePath: $templatesPath,
            cachePath: $cachePath,
        );
    }

    /**
     * Définit les variables disponibles dans tous les templates rendus
     * par cette instance (siteName, lang, i18n, etc.).
     *
     * @param array<string, mixed> $variables
     */
    public function setDefaultVariables(array $variables): void
    {
        $this->engine->setDefaultVariables($variables);
    }

    /**
     * Enregistre une macro utilisable dans les templates via ##name(args)##.
     */
    public function registerMacro(string $name, callable $callback): void
    {
        $this->engine->registerMacro($name, $callback);
    }

    /**
     * Rend un template Lunar et retourne le HTML produit.
     *
     * @param string $template Nom logique du template (sans extension .tpl).
     *                         Ex. 'pages/page' rend templates/pages/page.html.tpl.
     * @param array<string, mixed> $variables Variables propres à ce rendu.
     */
    public function render(string $template, array $variables = []): string
    {
        return $this->engine->render($template, $variables);
    }
}
