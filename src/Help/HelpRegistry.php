<?php

declare(strict_types=1);

namespace OliTheme\Help;

/**
 * Registre des guides d'aide in-admin.
 *
 * Source unique de vérité : liste hardcodée mappant un id stable
 * (utilisé dans les URLs admin et les bulles contextuelles) vers
 * un fichier Markdown versionné Git sous `docs/admin/`.
 *
 * @package OliTheme\Help
 *
 * @since 1.2.0
 */
final class HelpRegistry
{
    /**
     * Tous les guides disponibles, dans l'ordre d'affichage.
     *
     * @return list<HelpGuide>
     */
    public function all(): array
    {
        return [
            new HelpGuide('index', __('Accueil de l\'aide', 'oli-theme'), __('Vue d\'ensemble des guides du thème.', 'oli-theme'), 'index.md'),
            new HelpGuide('identite', __('Identité visuelle', 'oli-theme'), __('Logo, favicon, slogan, choix des médias.', 'oli-theme'), 'identite.md'),
            new HelpGuide('banniere', __('Bannière responsive', 'oli-theme'), __('Comment fonctionne l\'image de bannière sur tous les écrans.', 'oli-theme'), 'banniere.md'),
            new HelpGuide('apparence', __('Apparence & variations', 'oli-theme'), __('Variations CSS, police des titres, couleurs.', 'oli-theme'), 'apparence.md'),
            new HelpGuide('typo', __('Typographie', 'oli-theme'), __('Régler la taille des titres et des textes.', 'oli-theme'), 'typo.md'),
            new HelpGuide('contenu', __('Contenu (articles & pages)', 'oli-theme'), __('Création, édition, page d\'accueil, articles.', 'oli-theme'), 'contenu.md'),
            new HelpGuide('slides', __('Slides du carousel', 'oli-theme'), __('Créer une slide et choisir son image.', 'oli-theme'), 'slides.md'),
            new HelpGuide('galerie', __('Galerie photos & vidéos', 'oli-theme'), __('Ajouter des photos, légendes, vidéos YouTube.', 'oli-theme'), 'galerie.md'),
            new HelpGuide('menu', __('Menus', 'oli-theme'), __('Construire les menus et gérer leur affichage.', 'oli-theme'), 'menu.md'),
            new HelpGuide('traductions', __('Langues & traductions', 'oli-theme'), __('Activer les langues, lier les traductions, auditer.', 'oli-theme'), 'traductions.md'),
            new HelpGuide('footer', __('Pied de page', 'oli-theme'), __('Réseaux sociaux, logo et texte de bas de page.', 'oli-theme'), 'footer.md'),
            new HelpGuide('seo', __('SEO', 'oli-theme'), __('Tableau de bord SEO, métadonnées, scores.', 'oli-theme'), 'seo.md'),
            new HelpGuide('redirections', __('Redirections', 'oli-theme'), __('Gérer les redirections 301 personnalisées.', 'oli-theme'), 'redirections.md'),
            new HelpGuide('social', __('Réseaux sociaux', 'oli-theme'), __('Connecter les comptes sociaux affichés en footer.', 'oli-theme'), 'social.md'),
            new HelpGuide('calendrier', __('Calendrier (P1)', 'oli-theme'), __('Réservations cours et massages — fondations livrées, admin à venir.', 'oli-theme'), 'calendrier.md'),
            new HelpGuide('meta-sync', __('Synchro Facebook + Instagram (P1)', 'oli-theme'), __('Publication & propagation des modifications/suppressions — stockage chiffré livré.', 'oli-theme'), 'meta-sync.md'),
        ];
    }

    /**
     * Récupère un guide par son id ou null si inconnu.
     */
    public function byId(string $id): ?HelpGuide
    {
        foreach ($this->all() as $guide) {
            if ($guide->id === $id) {
                return $guide;
            }
        }

        return null;
    }
}
