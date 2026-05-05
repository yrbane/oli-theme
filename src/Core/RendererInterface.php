<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Contrat de rendu de template.
 *
 * Permet de découpler les consommateurs (modules, controllers) du moteur
 * de template concret (ViewRenderer / Lunar).
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
interface RendererInterface
{
    /**
     * Rend un template et retourne le HTML produit.
     *
     * @param string $template Nom logique du template (sans extension .tpl).
     * @param array<string, mixed> $variables Variables propres à ce rendu.
     */
    public function render(string $template, array $variables = []): string;
}
