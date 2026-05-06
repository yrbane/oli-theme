<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Wrapper immuable de la requête HTTP courante.
 *
 * Encapsule $_GET / $_POST / $_COOKIE / $_SERVER pour rendre les classes
 * dépendantes 100 % testables sans toucher aux superglobales en test.
 * Construit avec les valeurs réelles côté production via {@see self::fromGlobals()}.
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
final readonly class RequestContext
{
    /**
     * @param array<string, mixed> $query Variables de query string ($_GET).
     * @param array<string, mixed> $post Variables POST ($_POST).
     * @param array<string, string> $cookies Cookies ($_COOKIE).
     * @param array<string, mixed> $server Variables serveur ($_SERVER).
     */
    public function __construct(
        private array $query = [],
        private array $post = [],
        private array $cookies = [],
        private array $server = [],
    ) {
    }

    /**
     * Construit le contexte à partir des superglobales PHP courantes.
     */
    public static function fromGlobals(): self
    {
        /** @var array<string, mixed> $get */
        $get = $_GET;
        /** @var array<string, mixed> $post */
        $post = $_POST;
        /** @var array<string, string> $cookie */
        $cookie = $_COOKIE;
        /** @var array<string, mixed> $server */
        $server = $_SERVER;

        return new self($get, $post, $cookie, $server);
    }

    /**
     * Retourne la valeur d'une variable de query string, ou null si absente.
     */
    public function queryVar(string $name): ?string
    {
        $value = $this->query[$name] ?? null;

        return \is_scalar($value) ? (string) $value : null;
    }

    /**
     * Retourne la valeur d'un champ POST, ou null si absent.
     */
    public function postVar(string $name): ?string
    {
        $value = $this->post[$name] ?? null;

        return \is_scalar($value) ? (string) $value : null;
    }

    /**
     * Retourne la valeur d'un cookie, ou null si absent.
     */
    public function cookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Méthode HTTP en majuscules (GET par défaut).
     */
    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        return \is_string($method) ? strtoupper($method) : 'GET';
    }

    /**
     * Adresse IP du client. Retourne 0.0.0.0 si introuvable (ex. CLI).
     */
    public function ip(): string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';

        return \is_string($ip) ? $ip : '0.0.0.0';
    }

    /**
     * Lecture d'un en-tête HTTP via la convention $_SERVER['HTTP_*'].
     *
     * @example $ctx->header('Accept-Language')
     */
    public function header(string $name): ?string
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        $value = $this->server[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * Lecture d'une variable serveur brute (ex. REQUEST_URI, REQUEST_METHOD).
     */
    public function server(string $key): ?string
    {
        $value = $this->server[$key] ?? null;

        return \is_string($value) ? $value : null;
    }
}
