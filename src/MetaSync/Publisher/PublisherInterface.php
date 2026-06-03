<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Publisher;

use OliTheme\MetaSync\Http\GraphApiError;

/**
 * Contrat commun aux publishers Meta (Facebook, Instagram).
 *
 * @package OliTheme\MetaSync\Publisher
 *
 * @since 1.3.0
 */
interface PublisherInterface
{
    /**
     * Crée le post sur la plateforme. Retourne l'ID externe ou une erreur.
     *
     * @return string|GraphApiError
     */
    public function create(PublishPayload $payload): string|GraphApiError;

    /**
     * Édite le post existant. Retourne le nouvel ID externe (peut être le
     * même qu'avant si édition propre, ou un nouveau si recréation, ex. IG).
     *
     * @return string|GraphApiError
     */
    public function edit(string $externalId, PublishPayload $payload): string|GraphApiError;

    /**
     * Supprime le post externe. Retourne true/false ou une erreur Graph.
     *
     * @return bool|GraphApiError
     */
    public function delete(string $externalId): bool|GraphApiError;
}
