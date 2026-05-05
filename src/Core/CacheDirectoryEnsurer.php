<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Crée et vérifie l'écriture d'un répertoire de cache, sans jamais lever d'exception.
 *
 * Utilisé par le bootstrap pour s'assurer que `.cache/templates/` est utilisable
 * avant d'instancier le moteur Lunar (qui lève un fatal si le mkdir échoue).
 * En cas d'échec, l'appelant peut afficher un admin_notice plutôt que casser
 * la page entière. Voir issue #1.
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
final class CacheDirectoryEnsurer
{
    private ?string $lastError = null;

    /**
     * Garantit que `$path` est un répertoire writable.
     *
     * Crée le répertoire s'il n'existe pas. Retourne `false` si le chemin
     * existe mais n'est pas un répertoire, si la création échoue, ou si le
     * répertoire n'est pas writable. Le message d'erreur est exposé via
     * `getError()` pour permettre un admin_notice côté WP.
     */
    public function ensure(string $path): bool
    {
        $this->lastError = null;

        if (file_exists($path) && !is_dir($path)) {
            $this->lastError = \sprintf(
                'Le chemin %s existe mais n\'est pas un répertoire.',
                $path,
            );

            return false;
        }

        if (!is_dir($path) && !@mkdir($path, 0o755, true) && !is_dir($path)) {
            $parent          = \dirname($path);
            $this->lastError = \sprintf(
                'Impossible de créer le répertoire %s (parent : %s, writable=%s).',
                $path,
                $parent,
                is_writable($parent) ? 'oui' : 'non',
            );

            return false;
        }

        if (!is_writable($path)) {
            $this->lastError = \sprintf(
                'Le répertoire %s existe mais n\'est pas writable par PHP (UID=%s).',
                $path,
                \function_exists('posix_geteuid') ? (string) posix_geteuid() : 'inconnu',
            );

            return false;
        }

        return true;
    }

    /**
     * Retourne le dernier message d'erreur produit par `ensure()`, ou `null`.
     */
    public function getError(): ?string
    {
        return $this->lastError;
    }
}
