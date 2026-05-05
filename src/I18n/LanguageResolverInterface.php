<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Contrat du résolveur de langue courante.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
interface LanguageResolverInterface
{
    /**
     * Résout la langue selon l'ordre de priorité défini.
     */
    public function resolve(): Language;

    /**
     * Alias mémoïsé de resolve(). Conservé pour la lisibilité côté appelant.
     */
    public function current(): Language;
}
