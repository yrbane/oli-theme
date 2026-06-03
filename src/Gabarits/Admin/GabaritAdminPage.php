<?php

declare(strict_types=1);

namespace OliTheme\Gabarits\Admin;

use OliTheme\Admin\AdminTabInterface;
use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritRegistryInterface;

/**
 * Sous-onglet « Gabarits » de l'admin (groupe Apparence) qui affiche la
 * galerie des gabarits disponibles avec leur description et un aperçu de
 * couleur, pour qu'Olivier puisse comprendre l'effet de chaque style avant
 * de l'appliquer sur un post.
 *
 * @package OliTheme\Gabarits\Admin
 *
 * @since 1.4.0
 */
final class GabaritAdminPage implements AdminTabInterface
{
    public function __construct(private readonly GabaritRegistryInterface $registry)
    {
    }

    public function id(): string
    {
        return 'gabarits';
    }

    public function group(): string
    {
        return 'apparence';
    }

    public function label(): string
    {
        return __('Gabarits', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        $gabarits = $this->registry->all();
        ?>
        <p><?php esc_html_e('Les gabarits sont des styles de présentation appliqués à un post, une page ou un événement. Chaque contenu peut choisir son gabarit depuis la metabox « Gabarit » dans l\'éditeur.', 'oli-theme'); ?></p>
        <p><?php esc_html_e('Pour ajouter un nouveau gabarit : créer un dossier dans `assets/gabarits/{slug}/` avec un `manifest.json` et un `style.css` (et optionnellement un `script.js`). Le thème détecte automatiquement les nouveaux gabarits.', 'oli-theme'); ?></p>

        <div class="oli-gabarit-grid">
            <?php foreach ($gabarits as $g): /** @var Gabarit $g */ ?>
                <article class="oli-gabarit-card">
                    <div class="oli-gabarit-card__swatch" style="background: <?php echo esc_attr($g->previewColor); ?>;"></div>
                    <h3 class="oli-gabarit-card__title"><?php echo esc_html($g->name); ?></h3>
                    <code class="oli-gabarit-card__id"><?php echo esc_html($g->id); ?></code>
                    <p class="oli-gabarit-card__desc"><?php echo esc_html($g->description); ?></p>
                    <p class="oli-gabarit-card__meta">
                        <?php
                        $labels = [
                            'post'      => 'Articles',
                            'page'      => 'Pages',
                            'oli_event' => 'Événements',
                        ];
                        $supports = array_map(static fn (string $t): string => $labels[$t] ?? $t, $g->supports);
                        echo esc_html(implode(' · ', $supports));
                        if ($g->parallax) {
                            echo ' · <em>Parallaxe</em>';
                        }
                        if ($g->jsPath !== null) {
                            echo ' · <em>JS</em>';
                        }
                        ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
        <style>
            .oli-gabarit-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; margin: 1rem 0 2rem; }
            .oli-gabarit-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 1rem; }
            .oli-gabarit-card__swatch { height: 60px; border-radius: 4px; margin-bottom: 0.75rem; }
            .oli-gabarit-card__title { margin: 0 0 0.25rem; font-size: 1.05rem; }
            .oli-gabarit-card__id { font-size: 0.75rem; color: #757575; }
            .oli-gabarit-card__desc { font-size: 0.85rem; color: #50575e; line-height: 1.5; margin: 0.5rem 0; }
            .oli-gabarit-card__meta { font-size: 0.78rem; color: #757575; margin: 0; }
        </style>
        <?php
    }
}
