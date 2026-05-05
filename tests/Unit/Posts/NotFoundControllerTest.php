<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Posts\NotFoundController;
use PHPUnit\Framework\TestCase;

final class NotFoundControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRendersFourOhFourTemplate(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($french);

        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->willReturn(new LanguageSwitcherViewModel(current: $french, items: []));

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/404.html',
                self::callback(static fn (array $vm): bool => $vm['bodyClasses'] === 'error404 lang-fr'),
            )
            ->willReturn('<html>404</html>');

        $controller = new NotFoundController($resolver, $switcher, $renderer);

        self::assertSame('<html>404</html>', $controller->render());
    }
}
