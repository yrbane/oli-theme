<?php

/**
 * Pontage WordPress → page d'accueil.
 *
 * Délègue à {@see FrontPageController} qui sait choisir la bonne page selon
 * la langue courante : si `page_on_front` a une traduction pour la langue
 * active, c'est elle qui est rendue (corrige le bug où /en/ rendait toujours
 * la page d'accueil FR).
 *
 * Si `page_on_front` n'est pas défini, l'archive des articles filtrée par
 * langue est rendue à la place.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\FrontPageController;
use OliTheme\Theme;

echo Theme::container()->get(FrontPageController::class)->render();
