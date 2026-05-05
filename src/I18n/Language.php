<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Value object immuable décrivant une langue activée du thème.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final readonly class Language
{
    public function __construct(
        public string $code,
        public string $label,
        public string $nativeLabel,
        public string $flag,
        public string $locale,
        public string $direction = 'ltr',
    ) {
    }

    /**
     * Égalité sur le code de langue.
     */
    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}
