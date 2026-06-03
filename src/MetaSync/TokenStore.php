<?php

declare(strict_types=1);

namespace OliTheme\MetaSync;

/**
 * Persistance chiffrée des identifiants Meta Graph API.
 *
 * Encode/décode {@see MetaSyncCredentials} via AES-256-CBC. La clé de
 * chiffrement est dérivée de la constante WordPress `AUTH_KEY` (ou d'une
 * clé fournie via constructeur en tests), HKDF-like via `hash_hmac`.
 *
 * Le payload stocké en option est : `base64(IV).base64(ciphertext)`.
 * Toute altération du base64 ou de la clé invalide le déchiffrement
 * et retourne des credentials vides.
 *
 * @package OliTheme\MetaSync
 *
 * @since 1.3.0
 */
final class TokenStore
{
    public const OPTION_KEY = 'oli_meta_sync_credentials';
    private const CIPHER    = 'aes-256-gcm';
    private const IV_LEN    = 12;
    private const TAG_LEN   = 16;

    public function __construct(private readonly string $secretKey)
    {
        if ($this->secretKey === '') {
            throw new \InvalidArgumentException('TokenStore requires a non-empty secret key.');
        }
    }

    /**
     * Chiffre + persiste les credentials dans l'option WordPress.
     */
    public function save(MetaSyncCredentials $credentials): void
    {
        $plaintext = (string) json_encode($credentials->toArray());
        $payload   = $this->encrypt($plaintext);
        if (\function_exists('update_option')) {
            update_option(self::OPTION_KEY, $payload, false);
        }
    }

    /**
     * Lit + déchiffre les credentials. Retourne des credentials vides
     * si la persistance est absente ou si le déchiffrement échoue.
     */
    public function load(): MetaSyncCredentials
    {
        $raw = \function_exists('get_option') ? (string) get_option(self::OPTION_KEY, '') : '';
        if ($raw === '') {
            return new MetaSyncCredentials();
        }
        $plaintext = $this->decrypt($raw);
        if ($plaintext === null) {
            return new MetaSyncCredentials();
        }
        $arr = json_decode($plaintext, true);

        return \is_array($arr) ? MetaSyncCredentials::fromArray($arr) : new MetaSyncCredentials();
    }

    /**
     * Efface les credentials (déconnexion explicite par Olivier).
     */
    public function clear(): void
    {
        if (\function_exists('delete_option')) {
            delete_option(self::OPTION_KEY);
        }
    }

    /**
     * Chiffre via AES-256-GCM (authentifié) et retourne `base64(IV).base64(TAG).base64(CT)`.
     */
    public function encrypt(string $plaintext): string
    {
        $iv  = random_bytes(self::IV_LEN);
        $key = $this->derivedKey();
        $tag = '';
        $ct  = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);
        if ($ct === false) {
            return '';
        }

        return base64_encode($iv) . '.' . base64_encode($tag) . '.' . base64_encode($ct);
    }

    /**
     * Déchiffre un payload encodé. Retourne null en cas d'échec (format,
     * base64, clé, tampering — l'auth tag GCM est vérifié).
     */
    public function decrypt(string $payload): ?string
    {
        $parts = explode('.', $payload, 3);
        if (\count($parts) !== 3) {
            return null;
        }
        $iv  = base64_decode($parts[0], true);
        $tag = base64_decode($parts[1], true);
        $ct  = base64_decode($parts[2], true);
        if (!\is_string($iv) || !\is_string($tag) || !\is_string($ct)) {
            return null;
        }
        if (\strlen($iv) !== self::IV_LEN || \strlen($tag) !== self::TAG_LEN) {
            return null;
        }
        $key   = $this->derivedKey();
        $plain = openssl_decrypt($ct, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        return \is_string($plain) ? $plain : null;
    }

    /**
     * Dérive une clé 32 octets via HMAC-SHA256 (« HKDF-like ») depuis le secret
     * fourni — typiquement `AUTH_KEY` côté production.
     */
    private function derivedKey(): string
    {
        return hash_hmac('sha256', 'oli-meta-sync.v1', $this->secretKey, true);
    }
}
