<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Contrat du contrôleur du switcher de langue.
 *
 * Permet de découpler les consommateurs du contrôleur concret (final) et
 * facilite le mocking dans les tests unitaires.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
interface LanguageSwitcherControllerInterface
{
    /**
     * Construit le ViewModel pour le post courant (0 = pas de post, ex. archive).
     */
    public function build(int $currentPostId): LanguageSwitcherViewModel;
}
