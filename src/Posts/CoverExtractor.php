<?php

declare(strict_types=1);

namespace OliTheme\Posts;

/**
 * Sépare la `<figure>` de tête (image bannière) du reste du contenu.
 *
 * Si le contenu HTML d'un post commence par une `<figure>`, cette classe
 * la détache afin que le template puisse la rendre au-dessus du titre.
 * Le contenu d'origine est restitué inchangé si aucune figure ne précède
 * le texte.
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class CoverExtractor
{
    /**
     * Sépare la première `<figure>` placée tout en début de contenu.
     *
     * @return array{cover: ?string, body: string}
     */
    public function split(string $content): array
    {
        if (preg_match('~^\s*(<figure\b[^>]*>.*?</figure>)\s*~is', $content, $matches) !== 1) {
            return ['cover' => null, 'body' => $content];
        }

        return [
            'cover' => $matches[1],
            'body'  => substr($content, \strlen($matches[0])),
        ];
    }
}
