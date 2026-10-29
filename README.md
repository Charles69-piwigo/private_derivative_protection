# Private Derivative Protection (pdp)

[Français](#français) · [English](#english)

---

## Français

Plugin [Piwigo](https://piwigo.org/) qui protège les photos, vidéos et PDF des albums privés contre l'accès direct par URL.

### Origine

Piwigo vérifie les droits d'accès quand on **navigue** sur le site : un invité ne voit pas les albums privés, ni leurs vignettes, ni leurs photos en grand format. Mais ce contrôle porte sur les pages, pas sur les fichiers eux-mêmes. Piwigo génère et met en cache des fichiers dérivés (miniatures, formats d'affichage) sur le disque, à un emplacement prévisible. Quiconque connaît ou devine l'URL directe d'un de ces fichiers peut y accéder, même si la photo appartient à un album privé. Il en va de même pour les originaux en pleine résolution.

Ce n'est pas un bug de Piwigo mais une limitation d'architecture connue du projet. pdp la referme.

### Fonctionnement

**Dérivés (miniatures, formats d'affichage)**
- Pour une photo privée (album privé, ou niveau de confidentialité supérieur à celui de l'invité), l'URL du dérivé est remplacée par une URL signée (jeton HMAC-SHA256, 3 heures de validité par défaut) qui passe par `protect.php`. Ce script revérifie le jeton avant de servir l'image.
- pdp force `derivative_url_style = 2` : toutes les miniatures, publiques comprises, passent par PHP. Le dossier de cache `_data/i/` peut ainsi être entièrement bloqué sans casser les albums publics.

**Originaux (pleine résolution), vidéos et PDF**
- Chemin natif de Piwigo (tailles, téléchargement) : pdp s'appuie sur `action.php` quand cela suffit, et redirige vers `serve_original.php` quand une vérification supplémentaire est nécessaire (album privé, niveau élevé).
- URLs construites directement par des plugins tiers (par exemple `super_zoom`, via son point d'accroche `get_original_url`) : elles sont routées vers `serve_original.php`.
- `serve_original.php` vérifie les droits (niveau de l'image, albums interdits à l'utilisateur) puis sert le fichier. Il gère les requêtes `Range` (curseur de lecture vidéo), les en-têtes CORS (lecture Chromecast avec le plugin `castor`) et l'affichage des PDF en pleine page.
- Le poster d'une vidéo (fichier du dossier `pwg_representative/`, utilisé par `piwigo-videojs`) est servi par le même script (`?rep=<id>`).

**Blocage de l'accès direct**
- À l'activation, des fichiers `.htaccess` (`Require all denied`) sont écrits dans `galleries/`, `upload/` et `_data/i/`. Ils sont supprimés à la désactivation.

### Installation

Activer le plugin met en place automatiquement les `.htaccess`, `original_url_protection = 'all'` et `derivative_url_style = 2`. La désactivation ou la désinstallation retire ces réglages.

**Prérequis :** Apache 2.4 avec `AllowOverride` autorisant les `.htaccess` sur les dossiers concernés. `mod_rewrite` n'est pas nécessaire.

### Nginx

Nginx ignore les fichiers `.htaccess` : la couche de blocage direct des fichiers n'est alors pas active. Les mécanismes PHP (`action.php`, `serve_original.php`, `protect.php`) restent opérationnels. pdp détecte nginx et affiche un avertissement dans l'administration.

### Compatibilité

Testé avec : `question_mark_in_urls = false`, `category_url_style = id-name`, `picture_url_style = file`, ShareAlbum (liens `?xauth=`), `piwigo-videojs`, et les plugins `super_zoom`, `castor`, `face_tag_editor`, `geo_tag_editor` (versions patchées).

Un plugin qui construit lui-même des URLs vers les originaux ou les dérivés, au lieu d'utiliser les mécanismes standards de Piwigo, peut ne pas être protégé automatiquement. Il apparaît alors en 403 dans la console du navigateur.

### Langues

Français, anglais.

---

## English

A [Piwigo](https://piwigo.org/) plugin that protects photos, videos and PDFs in private albums against direct access by URL.

### Origin

Piwigo checks access rights when users **browse** the site: a guest does not see private albums, their thumbnails or their full-size photos. But that check applies to pages, not to the files themselves. Piwigo generates and caches derivative files (thumbnails, display sizes) on disk, at a predictable location. Anyone who knows or guesses the direct URL of one of those files can fetch it, even if the photo belongs to a private album. The same goes for full-resolution originals.

This is not a Piwigo bug but a known architectural limitation of the project. pdp closes it.

### How it works

**Derivatives (thumbnails, display sizes)**
- For a private photo (private album, or a privacy level above the guest's), the derivative URL is replaced by a signed URL (HMAC-SHA256 token, valid for 3 hours by default) that goes through `protect.php`. That script re-checks the token before serving the image.
- pdp forces `derivative_url_style = 2`: all thumbnails, public ones included, go through PHP. The cache folder `_data/i/` can then be fully blocked without breaking public albums.

**Originals (full resolution), videos and PDFs**
- Native Piwigo path (sizes, download): pdp relies on `action.php` when that is enough, and redirects to `serve_original.php` when an extra check is required (private album, high level).
- URLs built directly by third-party plugins (for example `super_zoom`, through its `get_original_url` hook): they are routed to `serve_original.php`.
- `serve_original.php` checks permissions (image level, albums forbidden to the user) and then serves the file. It handles `Range` requests (video seek bar), CORS headers (Chromecast playback with the `castor` plugin) and full-page PDF display.
- A video's poster (a file in the `pwg_representative/` folder, used by `piwigo-videojs`) is served by the same script (`?rep=<id>`).

**Blocking direct access**
- On activation, `.htaccess` files (`Require all denied`) are written to `galleries/`, `upload/` and `_data/i/`. They are removed on deactivation.

### Installation

Activating the plugin automatically sets up the `.htaccess` files, `original_url_protection = 'all'` and `derivative_url_style = 2`. Deactivating or uninstalling removes these settings.

**Requirements:** Apache 2.4 with `AllowOverride` allowing `.htaccess` files on the folders involved. `mod_rewrite` is not required.

### Nginx

Nginx ignores `.htaccess` files, so the direct-access blocking layer is not active. The PHP mechanisms (`action.php`, `serve_original.php`, `protect.php`) keep working. pdp detects nginx and shows a warning in the administration.

### Compatibility

Tested with: `question_mark_in_urls = false`, `category_url_style = id-name`, `picture_url_style = file`, ShareAlbum (`?xauth=` links), `piwigo-videojs`, and the `super_zoom`, `castor`, `face_tag_editor` and `geo_tag_editor` plugins (patched versions).

A plugin that builds its own URLs to originals or derivatives, instead of using Piwigo's standard mechanisms, may not be protected automatically. It will show up as a 403 in the browser console.

### Languages

French, English.
