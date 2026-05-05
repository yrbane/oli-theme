<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * DTO immuable représentant une règle de redirection HTTP.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final readonly class RedirectEntity
{
    /**
     * @param int $id Identifiant unique en base.
     * @param string $source URI source (ex. : /ancienne-page).
     * @param string $target URL cible de la redirection.
     * @param int $code Code HTTP : 301, 302 ou 410.
     * @param int $hits Nombre de fois où la règle a été déclenchée.
     * @param \DateTimeImmutable $createdAt Date de création de la règle.
     */
    public function __construct(
        public int $id,
        public string $source,
        public string $target,
        public int $code,
        public int $hits,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
