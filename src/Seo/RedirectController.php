<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Contrôleur de redirection HTTP déclenché sur template_redirect.
 *
 * Résout une URI entrante en RedirectEntity et exécute la redirection (301/302)
 * ou la ressource supprimée (410). Délègue toutes les opérations base de données
 * à RedirectModelInterface.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class RedirectController
{
    /**
     * @param RedirectModelInterface $redirects Modèle de gestion des redirections.
     */
    public function __construct(private readonly RedirectModelInterface $redirects)
    {
    }

    /**
     * Traite une requête entrante et applique la règle de redirection si elle existe.
     *
     * @param string $requestUri URI de la requête (ex. : /ancienne-page).
     *
     * @return bool Vrai si une règle a été trouvée et appliquée, faux sinon.
     */
    public function handle(string $requestUri): bool
    {
        $entity = $this->redirects->findBySource($requestUri);

        if ($entity === null) {
            return false;
        }

        $this->redirects->incrementHits($entity->id);

        if ($entity->code === 410) {
            status_header(410);
            nocache_headers();
            wp_die(esc_html__('Cette ressource n\'est plus disponible.', 'oli-theme'), 410);
        }

        wp_safe_redirect($entity->target, $entity->code);

        return true;
    }
}
