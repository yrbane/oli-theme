<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Installateur de la table des redirections (`{prefix}oli_redirects`).
 *
 * Idempotent : la méthode `ensureInstalled()` peut être appelée à chaque
 * démarrage du thème. Elle compare la version stockée dans l'option WP
 * `oli_theme_db_version` avec la version courante (`self::DB_VERSION`) et
 * exécute la migration uniquement si nécessaire.
 *
 * Cela garantit la création de la table sur les sites où le thème est déjà
 * actif au moment du déploiement (un simple `git pull` ne déclenche pas le
 * hook `after_switch_theme` — issue #3).
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class RedirectInstaller
{
    /**
     * Version courante du schéma. Incrémentée à chaque modification du DDL.
     */
    public const DB_VERSION = '1.0.0';

    /**
     * Clé d'option WP stockant la version actuellement installée.
     */
    public const OPTION_KEY = 'oli_theme_db_version';

    /**
     * @param object $wpdb Instance `\wpdb` de WordPress (typée `object`
     *                     pour faciliter le mock en tests unitaires).
     *
     * @phpstan-param \wpdb $wpdb
     */
    public function __construct(private readonly object $wpdb)
    {
    }

    /**
     * Installe ou met à jour le schéma si la version stockée diffère de la
     * version courante. Appelé à chaque démarrage du thème depuis le hook
     * `init` (priorité 5, avant `template_redirect`).
     */
    public function ensureInstalled(): void
    {
        $stored = (string) get_option(self::OPTION_KEY, '');
        if ($stored === self::DB_VERSION) {
            return;
        }

        $this->install();
        update_option(self::OPTION_KEY, self::DB_VERSION);
    }

    /**
     * Exécute le DDL via `dbDelta()`. Appelable directement par le hook
     * `after_switch_theme` lors d'une activation propre du thème.
     */
    public function install(): void
    {
        if (!\function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $charset = $this->wpdb->get_charset_collate();
        $table = $this->tableName();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source varchar(2048) NOT NULL,
            target varchar(2048) NOT NULL,
            code smallint(3) NOT NULL DEFAULT 301,
            hits bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_idx (source(191))
        ) {$charset};";

        dbDelta($sql);
    }

    /**
     * Retourne true si la table existe en base.
     *
     * Permet aux consommateurs (`RedirectModel`) de court-circuiter
     * silencieusement leurs requêtes lorsque la migration n'a pas encore
     * été appliquée (par exemple pendant le tout premier `init`).
     */
    public function tableExists(): bool
    {
        $table = $this->tableName();
        $found = $this->wpdb->get_var(
            $this->wpdb->prepare('SHOW TABLES LIKE %s', $table),
        );

        return $found === $table;
    }

    /**
     * Nom complet de la table (préfixe wpdb inclus).
     */
    public function tableName(): string
    {
        return $this->wpdb->prefix . 'oli_redirects';
    }
}
