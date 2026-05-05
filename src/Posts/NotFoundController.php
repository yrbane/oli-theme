<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;

/**
 * Controller dédié au rendu 404.
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class NotFoundController
{
    public function __construct(
        private readonly LanguageResolverInterface $resolver,
        private readonly LanguageSwitcherControllerInterface $switcher,
        private readonly \OliTheme\Navigation\MenuControllerInterface $menus,
        private readonly RendererInterface $renderer,
    ) {
    }

    public function render(): string
    {
        $current = $this->resolver->current();

        return $this->renderer->render('pages/404.html', [
            'lang'             => $current,
            'languageSwitcher' => $this->switcher->build(0),
            'primaryMenu'      => $this->menus->buildPrimary($current),
            'footerMenu'       => $this->menus->buildFooter($current),
            'bodyClasses'      => 'error404 lang-' . $current->code,
        ]);
    }
}
