# Spécifications — Thème WordPress custom multi-sites

## 1. Contexte

Plusieurs sites WordPress doivent être créés ou refondus à partir d’un même socle graphique et technique.

Sites concernés à ce stade :

- `olikalari.com`
- `satsangham.com`
- `olivier.durillon.com`
- éventuellement `margeye.com`, si le format retenu convient également à Marge

Le document d’origine mélange :

- des besoins communs à tous les sites ;
- des besoins spécifiques au site `olikalari.com`.

Le présent document sépare ces deux niveaux afin de faciliter la conception d’un thème WordPress custom, simple, structuré et réutilisable.

---

## 2. Objectifs généraux

### 2.1 Déléguer l’infrastructure

L’objectif est de déléguer la mise en place technique des sites :

- installation WordPress ;
- configuration de l’hébergement ;
- mise en place du thème custom ;
- configuration des noms de domaine ;
- configuration des emails et redirections ;
- sécurisation minimale ;
- sauvegardes ;
- maintenance.

### 2.2 Limiter la charge de gestion

La solution retenue ne doit pas être trop lourde à maintenir au quotidien.

Le propriétaire des sites souhaite éviter de perdre le contrôle de ses sites à cause :

- de mises à jour WordPress difficiles à gérer ;
- de plugins trop nombreux ou instables ;
- d’une interface d’administration trop complexe ;
- d’une dépendance excessive à un prestataire pour les modifications courantes.

Un budget peut être prévu pour garantir une solution professionnelle, stable et pérenne.

### 2.3 Rendre les utilisateurs autonomes

À terme, les utilisateurs doivent pouvoir gérer eux-mêmes l’essentiel des contenus :

- pages ;
- textes ;
- images ;
- galeries ;
- événements ;
- traductions ;
- informations pratiques ;
- contenus SEO de base.

Un temps ou budget de support doit toutefois être prévu pour :

- les dépannages ;
- les mises à jour ;
- les ajustements de modules spécifiques ;
- les évolutions fonctionnelles.

---

## 3. Principe technique attendu

### 3.1 WordPress

Tous les sites seront réalisés avec WordPress.

### 3.2 Thème custom

Le projet doit reposer sur un thème WordPress custom :

- sans dépendances lourdes ;
- sans builder visuel complexe ;
- sans empilement inutile de plugins ;
- structuré proprement ;
- facile à maintenir ;
- facile à utiliser côté administration.

### 3.3 Réutilisation multi-sites

Le thème doit pouvoir être utilisé sur plusieurs sites avec :

- une structure commune ;
- des options de personnalisation simples ;
- des contenus propres à chaque site ;
- des menus différents selon les besoins ;
- une identité visuelle adaptable.

---

## 4. Socle commun à tous les sites

## 4.1 Identité visuelle

Chaque site doit disposer d’une bannière permanente en haut de page.

Cette bannière doit :

- être rectangulaire et relativement étroite ;
- servir d’identité visuelle principale ;
- rester visible en haut du site ;
- permettre un retour à la page d’accueil au clic ;
- être adaptée aux écrans desktop, tablette et mobile.

## 4.2 Page d’accueil

La page d’accueil doit intégrer, sous le header, une galerie photo défilante.

Cette galerie doit permettre d’afficher notamment :

- des visuels de présentation ;
- des flyers d’événements à venir ;
- des annonces importantes ;
- des éléments promotionnels visibles immédiatement lors de l’arrivée sur le site.

Objectif : permettre aux visiteurs de tomber automatiquement sur les informations importantes dès l’arrivée sur le site.

## 4.3 Navigation

Chaque site doit disposer d’un menu principal avec sous-menus.

Le menu doit :

- supporter plusieurs niveaux d’arborescence ;
- afficher les sous-menus au survol sur desktop ;
- rester lisible et pratique sur mobile ;
- permettre des structures complexes si nécessaire ;
- être entièrement administrable depuis WordPress.

## 4.4 Multilingue

Les sites doivent pouvoir gérer plusieurs langues.

Contraintes attendues :

- nombre de langues potentiellement extensible ;
- pages séparées par langue ;
- arborescences indépendantes par langue ;
- URLs structurées par langue, par exemple :
  - `/fr/`
  - `/en/`
  - `/it/`
  - éventuellement `/es/` à terme ;
- accès permanent au changement de langue, idéalement dans le header ;
- possibilité d’utiliser des drapeaux ou libellés de langue ;
- absence de mélange de plusieurs langues dans une même page ;
- absence de répétition automatique d’un article dans une autre langue en bas de page.

Le propriétaire souhaite éviter les systèmes qui affichent automatiquement plusieurs langues sur la même page.

Chaque version linguistique doit pouvoir vivre de façon autonome.

Exceptions possibles :

- redirection vers un contenu commun si aucune traduction n’existe ;
- contenus génériques volontairement mutualisés, par exemple galerie ou réservation.

## 4.5 Pages et posts

Le rôle des posts WordPress doit être clarifié.

Questions à traiter :

- Les posts sont-ils utiles pour le référencement ?
- Doivent-ils servir aux actualités ?
- Doivent-ils alimenter les réseaux sociaux ?
- Doivent-ils être utilisés pour les événements ?
- Les contenus principaux doivent-ils plutôt rester sous forme de pages ?

Le propriétaire constate actuellement des problèmes avec :

- la navigation mobile ;
- les redirections ;
- le multilingue ;
- la compréhension de l’intérêt réel des posts.

## 4.6 Responsive design

Le site doit être pleinement responsive.

Objectifs :

- fonctionnement correct sur desktop, tablette et mobile ;
- adaptation automatique des contenus ;
- pas de retouches manuelles nécessaires pour la version mobile ;
- menu mobile simple ;
- galeries et médias adaptés aux petits écrans ;
- pages longues lisibles sur mobile.

## 4.7 Réseaux sociaux

Les sites doivent intégrer ou connecter les réseaux sociaux suivants :

- Facebook ;
- Instagram ;
- YouTube.

Besoins possibles :

- liens visibles vers les réseaux sociaux ;
- intégration de contenus récents ;
- intégration fluide d’une chaîne YouTube ;
- reprise ou déclinaison de contenus courts vers les réseaux sociaux.

Le plugin `Feed Them Social` a été évoqué, mais il doit être évalué avant décision.

Priorité : éviter une dépendance inutile à un plugin lourd si de simples liens ou intégrations natives suffisent.

## 4.8 Protection des contenus

Le propriétaire souhaite protéger certains textes longs contre la copie.

Besoins exprimés :

- protection des textes ;
- possibilité de proposer certains contenus en PDF ;
- watermark éventuel sur les PDF ;
- limitation de la copie non autorisée.

À noter : aucune solution web ne peut empêcher totalement la copie d’un contenu publié en ligne. Le besoin doit donc être traité comme une réduction du risque, et non comme une protection absolue.

Pistes possibles :

- PDF avec watermark ;
- mentions de droits d’auteur ;
- structuration claire des crédits ;
- limitation du téléchargement direct si nécessaire ;
- stratégie éditoriale distinguant contenus publics et contenus réservés.

## 4.9 Référencement naturel

Le propriétaire souhaite être autonome sur les bases du référencement.

Le site doit permettre de gérer simplement :

- titres SEO ;
- descriptions ;
- mots-clés éditoriaux ;
- structure des titres ;
- slugs d’URL ;
- textes alternatifs des images ;
- maillage interne ;
- extraits courts réutilisables ;
- métadonnées sociales.

Un guide d’utilisation doit être prévu pour expliquer :

- comment choisir les mots-clés ;
- comment structurer une page ;
- comment renseigner les métadonnées ;
- quand utiliser une page ;
- quand utiliser un post ;
- comment réutiliser un contenu pour les réseaux sociaux.

## 4.10 Réservation et contact

Les sites doivent pouvoir proposer un espace de contact et, selon le site, un système de réservation.

Fonctionnalités possibles :

- formulaire de contact ;
- adresse email dédiée ;
- redirection email vers une adresse principale ;
- agenda consultable ;
- prise de rendez-vous ;
- inscription à des événements ;
- paiement en ligne si nécessaire.

Pour chaque adresse email créée, il faut vérifier :

- qu’elle redirige correctement vers l’adresse principale ;
- que le fonctionnement est clair pour le propriétaire ;
- que la redirection est pérenne ;
- qu’un collaborateur pourra comprendre et reprendre le système.

---

# 5. Spécificités du site Olikalari

## 5.1 Positionnement éditorial

Le site `olikalari.com` doit présenter :

- l’enseignement traditionnel reçu au Kerala ;
- la continuité de cet enseignement ;
- la synthèse martiale originale développée par Olivier ;
- son parcours martial ;
- la dimension thérapeutique du Kalarippayat ;
- les cours, stages, soins et séjours proposés.

---

## 5.2 Arborescence proposée pour Olikalari

## 5.2.1 About / À propos

Objectif de la rubrique : présenter Olivier, son enseignement et sa démarche.

Texte d’intention :

> Olivier propose non seulement un enseignement traditionnel dans la continuité de celui qu’il a reçu au Kerala, mais également sa synthèse martiale originale, fruit de son propre parcours.

Pages envisagées :

- **Oli Kalari**
  - spécificité et état d’esprit de l’enseignant ;
  - parcours martial.

Sous-pages possibles :

- **Spécificité**
  - recherche de l’art martial principiel.
- **Parcours martial détaillé**

- **Le Kalarippayat**
  - présentation détaillée de l’histoire ;
  - présentation des différents styles ;
  - spécificités de l’école ;
  - renvoi vers la partie soins pour la dimension thérapeutique.

Sous-pages possibles :

- **Origine mythique et histoire**
- **Style du Nord**
- **Style du Sud**
- **Style du Centre**
- **L’école KKA**

---

## 5.2.2 Entraînements

Objectif de la rubrique : présenter les différentes façons d’aborder la pratique du Kalarippayat.

La rubrique doit montrer que l’enseignement peut être adapté :

- à différents publics ;
- à différents niveaux ;
- à différents objectifs ;
- avec des ponts possibles vers d’autres disciplines.

Contenus à prévoir :

- présentation générale des entraînements ;
- déclinaison des stages thématiques ;
- liens vers les formules de pratique ;
- éventuels liens vers les événements à venir.

---

## 5.2.3 Soins

Objectif de la rubrique : présenter la tradition thérapeutique du Kalarippayat et l’offre d’accompagnement proposée par Olivier.

Pages envisagées :

- **Tradition thérapeutique du Kalarippayat**
- **Formules d’accompagnement proposées**
  - parcours Olivier Durillon Soins.

Sous-pages possibles :

- **Massages**
  - formules ;
  - tarifs.
- **Maïeutique et corps subtil**
- autres accompagnements à préciser.

---

## 5.2.4 Formules

Objectif de la rubrique : présenter les différentes modalités d’accès à l’enseignement.

Pages envisagées :

- **Cours hebdomadaires**
- **Cours particuliers**
- **Stages**
- **Séjours**

Chaque formule devrait idéalement préciser :

- le public concerné ;
- le lieu ;
- le format ;
- la durée ;
- le tarif ou une indication de tarif ;
- les modalités d’inscription ;
- les prérequis éventuels.

---

## 5.2.5 Événements

Objectif de la rubrique : présenter les événements à venir et conserver un historique des événements passés.

Besoins :

- liste des événements à venir ;
- fiches événements ;
- visuels ou flyers ;
- dates ;
- lieux ;
- inscription ;
- historique des événements réalisés.

À clarifier techniquement :

- événements sous forme de pages ;
- événements sous forme de posts ;
- événements sous forme de type de contenu dédié ;
- intégration ou non avec un agenda.

---

## 5.2.6 Galerie

Objectif de la rubrique : présenter les contenus photo et vidéo.

Deux entrées principales :

- **Photos**
  - galerie défilante ;
  - albums éventuels ;
  - classement par événement ou thématique.

- **Vidéos**
  - intégration fluide de la chaîne YouTube ;
  - vidéos sélectionnées ;
  - éventuelles playlists.

---

## 5.2.7 Réservation

Objectif de la rubrique : centraliser les prises de contact, réservations et inscriptions.

Pages ou modules envisagés :

- **Nous contacter**
  - formulaire de contact ;
  - informations pratiques ;
  - email de contact.

- **Agenda interactif**
  - disponibilités de l’intervenant ;
  - réservation de soins ;
  - réservation de cours particuliers ;
  - consultation du planning des événements programmés.

- **Inscription**
  - inscription aux cours, stages ou séjours ;
  - paiement en ligne si nécessaire.

Point spécifique : vérifier que toute adresse email créée est bien redirigée vers `kalarirhone@gmail.com` et que ce fonctionnement reste clair, durable et facilement transmissible.

---

# 6. Besoins d’administration

L’interface WordPress doit être simple pour les utilisateurs finaux.

Les administrateurs doivent pouvoir modifier facilement :

- les textes ;
- les images ;
- les menus ;
- la galerie d’accueil ;
- les événements ;
- les traductions ;
- les informations de contact ;
- les liens vers les réseaux sociaux ;
- les métadonnées SEO principales.

Le thème doit éviter les options inutiles et privilégier des champs clairs.

---

# 7. Points à clarifier avant développement

## 7.1 Choix du système multilingue

Décider si le multilingue sera géré :

- par une extension spécialisée ;
- par une architecture WordPress multisite ;
- par une gestion custom légère ;
- par une autre approche.

Critère prioritaire : obtenir des arborescences propres, séparées et fiables par langue.

## 7.2 Rôle des posts

Décider si les posts servent à :

- publier des actualités ;
- publier des événements ;
- améliorer le référencement ;
- produire des contenus courts réutilisables sur les réseaux sociaux ;
- ou s’ils doivent être évités au profit de pages structurées.

## 7.3 Gestion des événements

Décider si les événements seront :

- de simples pages ;
- des articles ;
- un type de contenu dédié ;
- gérés par un plugin d’événements ;
- reliés à un agenda externe.

## 7.4 Réservation et paiement

Clarifier le niveau attendu :

- simple formulaire de demande ;
- agenda de disponibilités ;
- réservation automatique ;
- inscription avec validation manuelle ;
- paiement en ligne ;
- facturation éventuelle.

## 7.5 Protection des contenus

Clarifier les contenus à protéger :

- textes publics ;
- PDF ;
- contenus longs ;
- contenus réservés ;
- supports pédagogiques.

Définir ensuite le niveau réaliste de protection.

## 7.6 Réseaux sociaux

Clarifier le besoin réel :

- simples liens vers les réseaux ;
- affichage des derniers posts ;
- intégration YouTube uniquement ;
- publication automatique depuis WordPress vers les réseaux ;
- réutilisation manuelle des contenus.

---

# 8. Recommandation d’organisation du thème

Le thème devrait être organisé autour de composants simples :

- header ;
- menu desktop ;
- menu mobile ;
- sélecteur de langue ;
- galerie d’accueil ;
- blocs de contenu ;
- galerie photo ;
- intégration vidéo ;
- formulaire de contact ;
- footer ;
- modèles de pages ;
- modèles d’archives si les posts ou événements sont utilisés.

Le thème doit rester volontairement sobre afin de faciliter :

- la maintenance ;
- les mises à jour ;
- la prise en main ;
- la réutilisation sur plusieurs sites ;
- la performance ;
- la stabilité.

---

# 9. Livrables souhaitables

## 9.1 Livrables techniques

- Thème WordPress custom réutilisable.
- Configuration de base pour chaque site.
- Structure responsive.
- Menus administrables.
- Gestion de la galerie d’accueil.
- Gestion du multilingue.
- Modèles de pages nécessaires.
- Configuration SEO de base.
- Configuration des formulaires.
- Documentation d’installation et de maintenance.

## 9.2 Livrables utilisateur

- Guide simple pour modifier les pages.
- Guide simple pour gérer les menus.
- Guide simple pour gérer les langues.
- Guide simple pour publier un événement ou une actualité.
- Guide simple pour renseigner les informations SEO.
- Guide simple pour mettre à jour les images et flyers.

---

# 10. Priorités fonctionnelles

## Priorité 1 — indispensable

- Thème custom stable.
- Header permanent.
- Menu avec sous-menus.
- Responsive complet.
- Pages administrables.
- Multilingue propre.
- Galerie d’accueil.
- Contact simple.
- SEO de base.

## Priorité 2 — importante

- Galerie photo avancée.
- Intégration YouTube.
- Gestion structurée des événements.
- Guide utilisateur.
- Connexion aux réseaux sociaux.
- Redirections email propres.

## Priorité 3 — à étudier

- Agenda interactif.
- Réservation automatisée.
- Paiement en ligne.
- Watermark PDF.
- Publication automatique vers les réseaux sociaux.
- Protection avancée des contenus.
