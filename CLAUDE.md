# CLAUDE.md

Ce fichier guide Claude Code (claude.ai/code) pour travailler sur ce dépôt.

## De quoi s'agit-il

`private_derivative_protection` (pdp) est un plugin [Piwigo](https://piwigo.org/) (PHP, sans build, sans gestionnaire de paquets, sans tests). Il protège les fichiers de photos/vidéos/PDF appartenant à des albums privés (ou dont le niveau de confidentialité dépasse celui de l'invité) : un `.htaccess` interdit l'accès direct aux fichiers, et le plugin génère des URLs signées ou passe par un script PHP qui vérifie les droits.

Pas d'outil de lint/test : le dossier est déposé dans `plugins/` d'un Piwigo. Ici le dépôt se trouve dans `Z:\plugins\private_derivative_protection`, et `Z:\` est la racine du Piwigo de dev (cœur lisible dans `Z:\include`, `Z:\picture.php`, etc.). Pour tester, recharger une page photo/vidéo du site de dev. Vérification de syntaxe : `php -l fichier.php`.

## Fichiers

- **`main.inc.php`** : tous les points d'accroche + l'historique des versions en commentaire (à mettre à jour à chaque incrément).
- **`maintain.inc.php`** : à l'activation, force `original_url_protection='all'` et `derivative_url_style=2`, et écrit un `.htaccess` `Require all denied` dans `galleries/`, `upload/` et `_data/i/` (supprimé à la désactivation/désinstallation).
- **`protect.php`** : sert les dérivés (vignettes) via URL signée (`p`, `e`, `t` = chemin, expiration, HMAC-SHA256 avec `secret_key`). Il n'inclut volontairement pas `common.inc.php`.
- **`serve_original.php`** : sert les originaux après vérification des droits (niveau de l'image, albums interdits de l'utilisateur). Gère `Range` (seek vidéo), CORS (Chromecast / plugin castor) et PDF inline. `?id=<id>` = original ; `?rep=<id>` = fichier représentatif (poster de vidéo, `pwg_representative/<nom>.<representative_ext>`).
- **`admin.php`**, **`template/`**, **`language/{fr_FR,en_UK}/plugin.lang.php`** : page d'aide/admin. Toute nouvelle chaîne doit exister dans les deux langues.
- **`generate-release-zip.ps1`** : génère le zip de publication.

## Points d'accroche (main.inc.php)

- `get_derivative_url` : remplace l'URL d'un dérivé d'image privée par `protect.php?p=…&e=…&t=…` (TTL `PDP_TOKEN_TTL`, à garder identique dans `protect.php`).
- `get_src_image_url` (priorité NEUTRAL+10, après le handler du cœur) : originaux privés → `serve_original.php?id=`.
- `get_original_url` : **pas un événement du cœur**, c'est un contrat privé déclenché par `super_zoom`. Réécrit toujours vers `serve_original.php`.
- `render_element_content` (NEUTRAL-10) : affiche les PDF via `<object>` pointant sur `serve_original.php`.
- `render_element_content` (NEUTRAL+10) : après piwigo-videojs, remplace l'URL brute du poster (`…/pwg_representative/<nom>.<ext>`) par `serve_original.php?rep=<id>`. Les chemins peuvent contenir des espaces non encodés : la regex remonte jusqu'au guillemet ouvrant.
- `loc_action_before_http_headers` : ajoute le support `Range` (206) à `action.php` pour les vidéos, car VideoJS bascule sur `action.php` quand `original_url_protection` est actif.

`pdp_image_is_private()` : une image est privée si son `level` dépasse celui de l'invité (`guest_id`, lu en base, jamais codé en dur) ou si aucun de ses albums n'est `public`.

## Pièges connus

- Tout chemin brut sous `galleries/` ou `upload/` renvoie 403 (`.htaccess`). Un 403 en console vient donc presque toujours d'un plugin tiers qui construit une URL à la main ; la solution est de la réécrire vers `serve_original.php`, pas d'assouplir le `.htaccess`.
- Les sous-titres `.vtt` et vignettes de survol `-th_*.jpg` de piwigo-videojs sont aussi sous `pwg_representative/` et ne sont pas encore traités.
- `serve_original.php` ne vérifie pas le chemin par `realpath()` : `galleries/` et `upload/` peuvent être des symlinks. La sécurité repose sur le chemin lu en base à partir d'un id entier.
- Les réécritures d'URL (`rewrite`) doivent rester désactivées côté Piwigo.
- Nginx : les `.htaccess` sont ignorés, un avertissement s'affiche dans l'admin.

## Versions et publication

- L'en-tête de `main.inc.php` porte `Version: auto` (ne jamais le modifier) : PEM lit le tag git. L'historique en commentaire en tête de fichier est tenu à la main (date `jj/mm/aaaa`).
- Le plugin n'a pas encore été publié sur PEM ; les tags `v1.x` existants sont des jalons de développement.
- Numérotation : voir le `CLAUDE.md` global. Incréments Claude = `<dernier tag>.n` (ex. `1.5.1`, `1.5.2`), jamais `X.Ya` ni `X.(Y+1)`. Vérifier `git tag` avant de choisir.
- Messages de commit : `vX.Y - description` (voir `git log`).
