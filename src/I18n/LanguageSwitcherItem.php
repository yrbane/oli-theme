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
    /**
     * @param string $flag Emoji drapeau (fallback si flagUrl est null).
     * @param string|null $flagUrl URL absolue vers un SVG/PNG dans
     *                             `assets/img/flags/{code}.svg|.png` si présent.
     */
    public function __construct(
        public string $code,
        public string $label,
        public string $nativeLabel,
        public string $flag,
        public string $url,
        public bool $isCurrent,
        public bool $hasTranslation,
        public ?string $flagUrl = null,
    ) {
    }
}
