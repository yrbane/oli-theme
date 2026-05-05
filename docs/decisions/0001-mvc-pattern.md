# ADR 0001 — Pattern MVC strict appliqué à WordPress

**Date :** 2026-05-05
**Statut :** Accepté

## Contexte

WordPress n'est pas un framework MVC. Sa hiérarchie de templates `single.php`,
`page.php`, `archive.php` mêle traditionnellement HTML, requêtes SQL et logique
métier dans un même fichier PHP. Cette pratique est :

- difficile à tester (couplage fort à WordPress et au DOM rendu) ;
- difficile à maintenir sur le long terme ;
- difficile à transmettre à un autre prestataire ;
- en contradiction avec les principes SOLID exigés par le commanditaire.

## Décision

Le thème impose un pattern MVC strict, par discipline et par convention de
nommage :

- **Modèles** (`src/*/...Model.php`) : encapsulent la donnée (post types,
  meta, options, requêtes WP_Query). N'émettent jamais de HTML.
- **Contrôleurs** (`src/*/...Controller.php`) : orchestrent (récupèrent
  données via Models, préparent un ViewModel, appellent le Renderer).
  Ne contiennent jamais de HTML.
- **Vues** (`templates/**/*.html.tpl`) : templates Lunar Template Engine.
  N'appellent jamais de fonction WordPress, ne contiennent aucune logique
  métier ; seulement de l'affichage.
- **Modules** (`src/*/...Module.php`) : un par domaine fonctionnel
  (I18n, SEO, Events, ...). Enregistrent les hooks WordPress et instancient
  leurs contrôleurs.

Les fichiers de pontage WP (`theme-bridge/single.php`, `page.php`, ...)
contiennent **une seule ligne d'appel** au contrôleur correspondant.

## Conséquences

### Positives

- Tests unitaires possibles via Brain Monkey sans charger WordPress.
- Lisibilité : on sait où chercher chaque type de logique.
- Réutilisation : Models et Views évoluent indépendamment.
- Transmissibilité : convention claire pour tout futur prestataire.

### Négatives

- Plus de classes que dans un thème WP classique → courbe d'apprentissage.
- Discipline collective requise (un PR qui met du HTML dans un Model passe
  CI mais viole l'architecture — revue de code nécessaire).

## Alternatives écartées

- **Templates WP natifs** : rejeté (mélange vue/logique).
- **Timber/Twig** : rejeté au profit de Lunar Template (cf. ADR 0002).
- **Frameworks PHP type Laravel intégré à WP** : rejeté (overkill, violation
  des conventions WordPress et complexité de déploiement).
