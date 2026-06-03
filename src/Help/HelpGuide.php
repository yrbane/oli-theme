<?php

declare(strict_types=1);

namespace OliTheme\Help;

/**
 * Description immuable d'un guide d'aide in-admin.
 *
 * @package OliTheme\Help
 *
 * @since 1.2.0
 */
final readonly class HelpGuide
{
    public function __construct(
        public string $id,
        public string $title,
        public string $summary,
        public string $file,
    ) {
    }
}
