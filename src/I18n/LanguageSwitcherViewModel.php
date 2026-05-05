<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * ViewModel du switcher de langue.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final readonly class LanguageSwitcherViewModel
{
    /**
     * @param array<int, LanguageSwitcherItem> $items
     */
    public function __construct(
        public Language $current,
        public array $items,
    ) {
    }
}
