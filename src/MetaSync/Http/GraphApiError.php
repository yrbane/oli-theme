<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Http;

/**
 * Représente une réponse d'erreur Graph API.
 *
 * @package OliTheme\MetaSync\Http
 *
 * @since 1.3.0
 */
final readonly class GraphApiError
{
    public function __construct(
        public int $httpStatus,
        public string $type,
        public string $message,
        public int $graphCode = 0,
    ) {
    }

    public function isAuthError(): bool
    {
        // 190 = invalid access token, 102 = session has been invalidated.
        return $this->graphCode === 190 || $this->graphCode === 102 || $this->httpStatus === 401;
    }

    public function isPermissionError(): bool
    {
        return $this->graphCode === 200 || $this->graphCode === 10 || $this->graphCode === 100;
    }

    public function isRateLimited(): bool
    {
        return $this->graphCode === 4 || $this->graphCode === 17 || $this->graphCode === 32 || $this->httpStatus === 429;
    }
}
