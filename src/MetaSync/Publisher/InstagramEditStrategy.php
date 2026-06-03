<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Publisher;

/**
 * Stratégie de gestion des éditions Instagram.
 *
 * L'API Instagram ne permet PAS d'éditer la caption d'un média existant.
 * Olivier choisit l'arbitrage suivant :
 *  - `Skip` : ignorer l'édition (le post IG reste avec le contenu d'origine).
 *  - `DeleteRecreate` : supprimer le post IG et en créer un nouveau
 *    (perd l'URL IG et les likes accumulés).
 *
 * @package OliTheme\MetaSync\Publisher
 *
 * @since 1.3.0
 */
enum InstagramEditStrategy: string
{
    case Skip = 'skip';
    case DeleteRecreate = 'delete_recreate';
}
