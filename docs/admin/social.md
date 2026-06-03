# Réseaux sociaux — comptes affichés en footer

`Admin → Apparence du thème → Réseaux sociaux → Comptes` : déclarer les comptes affichés en pied de page sous forme d'icônes cliquables.

## Comptes supportés

Facebook, Instagram, X (ex-Twitter), LinkedIn, YouTube, TikTok, Pinterest, Bluesky, Mastodon, RSS.

## Format attendu

- **URL complète** (ex. `https://www.instagram.com/olikalari`).
- Une URL vide = l'icône correspondante n'apparaît pas.

## Rendu

Les icônes apparaissent :

- En **pied de page** sur toutes les pages du site.
- Avec une **couleur neutre** par défaut, qui **prend la couleur de marque** au survol (Facebook bleu, Instagram dégradé rose-orange, X noir, YouTube rouge, etc.).
- Ordre fixe (pas configurable depuis l'admin) — les icônes manquantes (URL vide) sont simplement masquées.

## Distinct de la synchronisation Meta

⚠ Ce sous-onglet **affiche** seulement les icônes en pied de page. Il **ne publie pas** automatiquement sur Facebook ou Instagram.

Pour la **publication automatique** (chaque article WP repris sur la Page FB et le compte IG) : voir le sous-onglet **Synchro Facebook / Instagram** dans le même groupe `Réseaux sociaux` (guide dédié `meta-sync.md`).

## Localisation des sous-onglets

L'onglet top-level **« Réseaux sociaux »** contient :

| Sous-onglet | Rôle | Guide |
|---|---|---|
| **Comptes** | URLs des profils sociaux affichés en footer | ce guide |
| **Synchro Facebook / Instagram** | Publication auto via Graph API | `meta-sync.md` |
