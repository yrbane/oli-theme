<?php

declare(strict_types=1);

namespace OliTheme\MetaSync;

/**
 * DTO immuable des identifiants Meta Graph API.
 *
 * Aucune sérialisation directe ne doit fuiter : utiliser exclusivement
 * {@see TokenStore} qui chiffre la persistance en base.
 *
 * @package OliTheme\MetaSync
 *
 * @since 1.3.0
 */
final readonly class MetaSyncCredentials
{
    public function __construct(
        public string $appId = '',
        public string $appSecret = '',
        public string $pageId = '',
        public string $igUserId = '',
        public string $accessToken = '',
        public int $expiresAt = 0,
    ) {
    }

    public function isConnected(): bool
    {
        return $this->accessToken !== '' && $this->pageId !== '';
    }

    /**
     * Vrai si l'expiration approche (< 7 jours).
     */
    public function isExpiringSoon(int $now): bool
    {
        return $this->expiresAt > 0 && ($this->expiresAt - $now) < 7 * 86400;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'appId'       => $this->appId,
            'appSecret'   => $this->appSecret,
            'pageId'      => $this->pageId,
            'igUserId'    => $this->igUserId,
            'accessToken' => $this->accessToken,
            'expiresAt'   => $this->expiresAt,
        ];
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            appId:       (string) ($raw['appId'] ?? ''),
            appSecret:   (string) ($raw['appSecret'] ?? ''),
            pageId:      (string) ($raw['pageId'] ?? ''),
            igUserId:    (string) ($raw['igUserId'] ?? ''),
            accessToken: (string) ($raw['accessToken'] ?? ''),
            expiresAt:   (int) ($raw['expiresAt'] ?? 0),
        );
    }
}
