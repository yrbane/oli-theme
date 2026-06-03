<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * @package OliTheme\Gabarits
 *
 * @since 1.4.0
 */
interface GabaritRegistryInterface
{
    /** @return list<Gabarit> */
    public function all(): array;

    public function byId(string $id): ?Gabarit;

    /** @return list<Gabarit> */
    public function forType(string $postType): array;
}
