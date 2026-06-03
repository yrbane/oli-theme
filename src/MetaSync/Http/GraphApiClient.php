<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Http;

/**
 * Client HTTP léger pour Meta Graph API.
 *
 * Wrappe `wp_remote_request` avec :
 *  - Base URL versionnée (configurable, défaut `https://graph.facebook.com/v19.0`).
 *  - Décodage JSON automatique.
 *  - Détection des erreurs Graph (`error.code`, `error.message`).
 *  - Token redacté dans les logs (`access_token=[REDACTED]`).
 *  - Timeout par défaut 15 s + 1 retry sur 5xx (200 ms backoff).
 *
 * Injectable via la closure `httpFn` pour la testabilité (signatures
 * compatibles `wp_remote_request`).
 *
 * @package OliTheme\MetaSync\Http
 *
 * @since 1.3.0
 */
final class GraphApiClient
{
    public const DEFAULT_BASE = 'https://graph.facebook.com/v19.0';

    public function __construct(
        private readonly string $baseUrl = self::DEFAULT_BASE,
        /** @var (callable(string,array<string,mixed>): array<string,mixed>)|null */
        private $httpFn = null,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>|GraphApiError
     */
    public function get(string $path, array $query = [], string $accessToken = ''): array|GraphApiError
    {
        $url = $this->buildUrl($path, $accessToken !== '' ? array_merge($query, ['access_token' => $accessToken]) : $query);

        return $this->request('GET', $url, []);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|GraphApiError
     */
    public function post(string $path, array $body, string $accessToken = ''): array|GraphApiError
    {
        $url = $this->buildUrl($path, []);
        if ($accessToken !== '') {
            $body['access_token'] = $accessToken;
        }
        return $this->request('POST', $url, ['body' => $body]);
    }

    /**
     * @return array<string, mixed>|GraphApiError
     */
    public function delete(string $path, string $accessToken = ''): array|GraphApiError
    {
        $url = $this->buildUrl($path, $accessToken !== '' ? ['access_token' => $accessToken] : []);
        return $this->request('DELETE', $url, []);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildUrl(string $path, array $params): string
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        if ($params !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        }
        return $url;
    }

    /**
     * @param array<string, mixed> $opts
     * @return array<string, mixed>|GraphApiError
     */
    private function request(string $method, string $url, array $opts): array|GraphApiError
    {
        $opts['method']      = $method;
        $opts['timeout']     ??= 15;
        $opts['redirection'] ??= 3;
        $opts['sslverify']   ??= true;

        $attempts = 0;
        $maxAttempts = 2;
        $response = [];
        while ($attempts < $maxAttempts) {
            $response = ($this->httpFn !== null)
                ? ($this->httpFn)($url, $opts)
                : (\function_exists('wp_remote_request') ? wp_remote_request($url, $opts) : ['__is_wp_error' => true, 'message' => 'wp_remote_request unavailable']);

            if (\is_array($response) && !empty($response['__is_wp_error'])) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    return new GraphApiError(0, 'http_error', (string) ($response['message'] ?? 'network error'));
                }
                usleep(200_000);
                continue;
            }

            $code = $this->extractResponseCode($response);
            if ($code >= 500 && $code < 600) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    return new GraphApiError($code, 'server_error', 'Graph API server error');
                }
                usleep(200_000);
                continue;
            }

            return $this->decodeBody($response, $code);
        }

        return new GraphApiError(0, 'unknown', 'Unknown error');
    }

    /**
     * @param mixed $response
     */
    private function extractResponseCode($response): int
    {
        if (\function_exists('wp_remote_retrieve_response_code')) {
            return (int) wp_remote_retrieve_response_code($response);
        }
        if (\is_array($response) && isset($response['response']['code'])) {
            return (int) $response['response']['code'];
        }
        return 0;
    }

    /**
     * @param mixed $response
     * @return array<string, mixed>|GraphApiError
     */
    private function decodeBody($response, int $code): array|GraphApiError
    {
        $body = '';
        if (\function_exists('wp_remote_retrieve_body')) {
            $body = (string) wp_remote_retrieve_body($response);
        } elseif (\is_array($response) && isset($response['body'])) {
            $body = (string) $response['body'];
        }
        $decoded = json_decode($body, true);
        if (!\is_array($decoded)) {
            return new GraphApiError($code, 'invalid_response', 'Non-JSON response from Graph API.');
        }
        if (isset($decoded['error']) && \is_array($decoded['error'])) {
            return new GraphApiError(
                $code,
                (string) ($decoded['error']['type'] ?? 'graph_error'),
                (string) ($decoded['error']['message'] ?? 'Graph API error'),
                (int) ($decoded['error']['code'] ?? 0),
            );
        }
        if ($code >= 400) {
            return new GraphApiError($code, 'http_' . $code, 'HTTP ' . $code);
        }
        return $decoded;
    }
}
