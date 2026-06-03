<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Publisher;

/**
 * DTO immuable des données extraites d'un post WordPress prêtes à
 * être publiées sur les réseaux sociaux.
 *
 * @package OliTheme\MetaSync\Publisher
 *
 * @since 1.3.0
 */
final readonly class PublishPayload
{
    public function __construct(
        public int $postId,
        public string $title,
        public string $excerpt,
        public string $contentText,
        public string $permalink,
        public string $featuredImageUrl = '',
        /** @var list<string> */
        public array $hashtags = [],
        public string $language = 'fr',
    ) {
    }

    /**
     * Hash stable du contenu publiable : détecte les vraies modifications
     * sans déclencher de PATCH inutile pour des changements de meta WP.
     */
    public function contentHash(): string
    {
        return hash('sha256', implode('|', [
            $this->title,
            $this->excerpt,
            $this->contentText,
            $this->permalink,
            $this->featuredImageUrl,
            implode(',', $this->hashtags),
            $this->language,
        ]));
    }

    /**
     * Message Facebook (titre + extrait/contenu tronqué + permalien + hashtags).
     */
    public function facebookMessage(): string
    {
        $body = $this->excerpt !== '' ? $this->excerpt : $this->contentText;
        $body = $this->truncate($body, 5000);
        $parts = [$this->title, '', $body, '', $this->permalink];
        if (!empty($this->hashtags)) {
            $parts[] = '';
            $parts[] = implode(' ', array_map(static fn (string $t): string => '#' . ltrim($t, '#'), $this->hashtags));
        }
        return implode("\n", array_filter($parts, static fn (string $p): bool => $p !== '' || $p === ''));
    }

    /**
     * Caption Instagram (limite 2200 chars).
     */
    public function instagramCaption(): string
    {
        $body = $this->excerpt !== '' ? $this->excerpt : $this->contentText;
        $parts = [$this->title, '', $this->truncate($body, 1800), '', __('Lien en bio', 'oli-theme')];
        if (!empty($this->hashtags)) {
            $parts[] = '';
            $parts[] = implode(' ', array_map(static fn (string $t): string => '#' . ltrim($t, '#'), $this->hashtags));
        }
        $caption = implode("\n", $parts);
        return $this->truncate($caption, 2200);
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        return rtrim($cut, " \t\n\r…") . '…';
    }
}
