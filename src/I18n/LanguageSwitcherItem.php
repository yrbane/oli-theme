<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Item du switcher de langue (un par langue activée).
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final readonly class LanguageSwitcherItem
{
    public function __construct(
        public string $code,
        public string $label,
        public string $nativeLabel,
        public string $flag,
        public string $url,
        public bool $isCurrent,
        public bool $hasTranslation,
    ) {
    }
}
