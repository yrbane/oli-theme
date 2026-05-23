<?php

declare(strict_types=1);

namespace OliTheme\Slides;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageTaxonomy;
use OliTheme\I18n\TranslationModel;

/**
 * Module Slides : enregistre le CPT oli_slide et les services associés
 * (modèle, contrôleur) dans le container.
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
final class SlidesModule implements ModuleInterface
{
    /**
     * @param Container $container Container de services du thème.
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Enregistre les services Slides et branche le CPT sur le hook 'init'.
     */
    public function register(): void
    {
        $container = $this->container;

        if (! $container->has(SlideCpt::class)) {
            $container->factory(
                SlideCpt::class,
                static fn (): SlideCpt => new SlideCpt(),
            );
        }

        if (! $container->has(SlideModel::class)) {
            $container->factory(
                SlideModel::class,
                static fn (Container $c): SlideModel => new SlideModel(
                    $c->get(LanguageRegistryInterface::class),
                ),
            );
        }

        if (! $container->has(SlideModelInterface::class)) {
            $container->factory(
                SlideModelInterface::class,
                static fn (Container $c): SlideModelInterface => $c->get(SlideModel::class),
            );
        }

        if (! $container->has(HomeCarouselController::class)) {
            $container->factory(
                HomeCarouselController::class,
                static fn (Container $c): HomeCarouselController => new HomeCarouselController(
                    $c->get(SlideModelInterface::class),
                    $c->get(LanguageResolverInterface::class),
                ),
            );
        }

        if (! $container->has(HomeCarouselControllerInterface::class)) {
            $container->factory(
                HomeCarouselControllerInterface::class,
                static fn (Container $c): HomeCarouselControllerInterface => $c->get(HomeCarouselController::class),
            );
        }

        add_action('init', function (): void {
            $this->container->get(SlideCpt::class)->register();
        });

        // Filtre REST : /wp/v2/oli_slide?lang=xx → restreint à la langue.
        add_filter('rest_oli_slide_query', static function (array $args, \WP_REST_Request $request): array {
            $lang = (string) $request->get_param('lang');
            if ($lang === '' || preg_match('/^[a-z]{2}$/', $lang) !== 1) {
                return $args;
            }
            $args['tax_query'] = [[
                'taxonomy' => LanguageTaxonomy::NAME,
                'field'    => 'slug',
                'terms'    => $lang,
            ]];

            return $args;
        }, 10, 2);

        // Carousel plein écran de la home (variation Olikalari) : livré avec le
        // thème. Enqueue uniquement sur la page d'accueil ou sa traduction.
        add_action('wp_enqueue_scripts', function (): void {
            if ((string) get_option('oli_theme_variation', '') !== 'olikalari') {
                return;
            }
            if (!$this->isHomeView()) {
                return;
            }
            $path = get_template_directory() . '/assets/js/home-carousel.js';
            wp_enqueue_script_module(
                'oli-home-carousel',
                get_template_directory_uri() . '/assets/js/home-carousel.js',
                [],
                file_exists($path) ? (string) filemtime($path) : null,
            );
        });
    }

    /**
     * Vrai sur la page d'accueil statique ou l'une de ses traductions
     * (même groupe de traduction que `page_on_front`).
     */
    private function isHomeView(): bool
    {
        if (\function_exists('is_front_page') && is_front_page()) {
            return true;
        }
        $queriedId = \function_exists('get_queried_object_id') ? (int) get_queried_object_id() : 0;
        $frontId   = (int) get_option('page_on_front', 0);
        if ($queriedId <= 0 || $frontId <= 0 || $queriedId === $frontId) {
            return false;
        }
        $frontGroup   = (string) get_post_meta($frontId, TranslationModel::META_KEY, true);
        $currentGroup = (string) get_post_meta($queriedId, TranslationModel::META_KEY, true);

        return $frontGroup !== '' && $frontGroup === $currentGroup;
    }
}
