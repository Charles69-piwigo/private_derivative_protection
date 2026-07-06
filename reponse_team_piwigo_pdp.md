# pdp — réponse aux remarques de relecture

Merci pour la relecture. Cette note reprend les remarques dans l'ordre où elles ont été posées et explique comment chacune est traitée dans la dernière version du plugin `private_derivative_protection` (pdp).

Le passage sous gestionnaire de code (remarque liminaire) est traité à part : le code est déjà versionné sous Git et sera connecté à un hébergement public (GitHub ou équivalent) pour la publication.

Environnement de validation : instance `photodev2`, Synology DSM 7, Apache 2.4 (sans mod_rewrite), PHP-FPM.

---

## 1. « Pourquoi `serve_original.php` si on a déjà `original_url_protection` / `action.php` ? »

La remarque est légitime. Il y a en fait **deux sources d'URL d'originaux** distinctes, et une seule est couverte par `action.php` :

- **Le chemin natif du core** (barre de tailles, bouton de téléchargement). C'est ce que gouverne `original_url_protection = 'all'` : le core génère des URLs `action.php?…&part=e` et `action.php` vérifie les droits. **pdp ne duplique pas ce chemin** : le hook `get_src_image_url` laisse `action.php` gérer le cas public standard, et ne réécrit vers `serve_original.php` **que** si une protection est réellement nécessaire (album privé ou niveau élevé).

- **Les URLs construites par des plugins tiers.** `super_zoom` construit lui-même une URL brute vers `galleries/…`, sans passer par la génération d'URL du core — donc sans passer par `action.php`. Cette URL est bloquée par le `.htaccess` (`Require all denied`). C'est ce cas que `serve_original.php` rattrape.

Donc `serve_original.php` n'est pas un doublon d'`action.php`. Il traite (a) les URLs brutes produites par des plugins qui court-circuitent `action.php`, et (b) l'application de `images.level`, qu'`action.php` n'effectue pas (cf. point 3).

Concrètement, deux hooks coexistent, chacun sur son propre point d'appel :

- **`get_src_image_url`** (évènement du core, chemin natif) — priorité `NEUTRAL + 10` pour s'exécuter après le handler du core et pouvoir remplacer son URL. Réécrit vers `serve_original.php` uniquement si album privé **ou** niveau élevé ; sinon laisse l'URL `action.php` inchangée.
- **`get_original_url`** — ce n'est pas un évènement du core, mais un point d'accroche déclenché par `super_zoom` lui-même (`trigger_change('get_original_url', …)` dans son `main.inc.php`) : un contrat entre les deux plugins. pdp l'écoute et réécrit systématiquement vers `serve_original.php`, car l'URL de départ est un chemin brut bloqué par le `.htaccess`.

`serve_original.php` effectue ensuite la vérification par requête (accès album + niveau) pour l'utilisateur réel, puis sert le fichier. Une image publique de niveau autorisé passe le contrôle ; un original d'album privé ou de niveau élevé renvoie un 403.

---

## 2. Mécanisme du token à durée de vie limitée

La compréhension est exacte. À la génération de la page, les URLs de dérivés (`i.php`) sont réécrites vers `protect.php` avec un token signé à durée de vie limitée. L'URL porte trois paramètres : `p` (chemin encodé), `e` (expiration, timestamp Unix), `t` (signature HMAC).

Quelques précisions :

- Les tokens sont **régénérés à chaque chargement de page** : une session de consultation légitime dispose toujours de tokens frais, il n'y a donc pas d'expiration en cours de navigation.
- La durée de vie est **paramétrable** (3 heures par défaut).
- Pour être précis sur la propriété de sécurité : la protection repose sur l'**expiration rapide**, pas sur un lien à la session. Une URL `protect.php` copiée reste valide tant que le token n'a pas expiré ; la courte durée de vie limite fortement le partage, sans l'empêcher de façon absolue. C'est un choix assumé (permettre le fonctionnement normal du navigateur, des caches et des CDN pendant la consultation).

---

## 3. Prise en compte de `images.level`

La remarque est juste : la détection ne regardait que l'accès à l'album. Elle a été corrigée aux deux points où pdp contrôle l'accès :

- **`pdp_image_is_private()`** (décision de réécriture des dérivés) teste désormais `images.level > niveau_invité` en plus du test d'album, combinés en **OU**. C'est le seul point de contrôle possible pour les dérivés : `i.php`, utilisé par `protect.php`, fait un bootstrap sans session ni `$user`, donc toute la décision de protection des dérivés repose sur ce prédicat.
- **`serve_original.php`** teste `images.level > $user['level']` (niveau de l'utilisateur réel de la requête), en plus du test `forbidden_categories` existant.
- Le niveau de l'invité est récupéré dynamiquement (`$conf['guest_id']` → `user_infos.level`), jamais codé en dur.

Une distinction utile : pour l'accès **navigué**, le core masque déjà en amont toute photo de niveau supérieur à celui de l'invité (vignettes, listes d'albums). L'apport de pdp porte sur l'accès **direct par URL** (rejouée, devinée, ou mise en cache), là où ce filtrage amont n'intervient pas.

Observation, à titre informatif, sur le chemin natif : dans la version que j'ai lue, `action.php` vérifie les droits via `get_sql_condition_FandF()` appelé avec `image_id`, or cette fonction n'ajoute la clause `level<=` que pour un `field_name` valant `id`/`i.id`. Sur ce chemin, le niveau n'est donc pas appliqué. C'est sans conséquence pour pdp : le hook `get_src_image_url` réécrit précisément les cas de niveau élevé vers `serve_original.php` (qui, lui, applique le niveau) **avant** qu'`action.php` ne les serve. Le niveau est donc appliqué côté plugin, indépendamment d'`action.php`.

---

## 4. Fonctionnement sous nginx

Confirmé : sous nginx, le `.htaccess` (`Require all denied` sur `galleries/` et `upload/`) est ignoré, car nginx ne lit pas les fichiers `.htaccess` et sert les fichiers statiques sans invoquer PHP. C'est l'apport **spécifique** de pdp — le blocage de l'accès direct au fichier — qui est inopérant sous nginx pur.

En revanche, `original_url_protection` + `action.php` sont de niveau PHP (fonctionnalité du **core**) et continuent de fonctionner quel que soit le serveur : le core ne divulgue pas les URLs directes des originaux et route leur accès par `action.php`. Ce qui disparaît sous nginx, ce n'est donc pas toute la protection des originaux, mais uniquement la défense en profondeur contre l'accès direct par chemin de fichier — qui est ce que pdp ajoute.

En pratique, pdp détecte nginx (condition double : `nginx` présent **et** `apache` absent dans `SERVER_SOFTWARE`, pour classer correctement les configurations hybrides Apache + nginx en proxy) et affiche un avertissement d'administration. Un équivalent du blocage `.htaccess` est possible via un bloc `location` dans la configuration nginx, mais celui-ci doit être ajouté à la conf serveur par un administrateur (un plugin ne peut ni écrire ni recharger la configuration nginx) et demande une attention particulière pour ne pas bloquer les originaux des albums publics.

C'est cette limitation qui empêche pour l'instant un test complet en environnement nginx pur — un point ouvert pour lequel des testeurs seraient les bienvenus.

---

## Tests de validation (`photodev2`)

| Scénario | Résultat |
|---|---|
| Original, album privé, invité (URL rejouée dans un autre navigateur) | 403 via `serve_original.php` |
| Original, album privé, utilisateur autorisé | Servi — non-régression |
| Original via `super_zoom`, photo protégée, URL rejouée hors session | 403 via `serve_original.php` |
| Original via `super_zoom`, photo publique, invité | Affiché — non-régression |
| Original, photo de niveau élevé en album **public**, invité (barre de tailles) | Réécrit vers `serve_original.php` → 403 |
| Dérivé (vignette), photo de niveau élevé en album **public** | `src` réécrit en `protect.php` (au lieu du chemin direct `_data/i/…`) |
| Vidéo (`castor`), album privé, URL rejouée hors session | 401 via `download_url` / `get_action_url()` — indépendant des présentes modifications |

Note : l'accès d'un invité aux originaux dépend par ailleurs du réglage « Autoriser le téléchargement » (`enabled_high`) du compte invité, indépendant de pdp ; les scénarios ci-dessus supposent ce réglage cohérent avec le résultat attendu.

---

## Limitations connues (documentées)

- **Chemins dérivés déterministes.** pdp corrige la *divulgation* des URLs directes (le HTML ne publie plus de chemin direct pour une image protégée), mais ne rend pas inaccessible un chemin `_data/i/…` déjà connu ou deviné, en dehors de la protection `.htaccess`. Sur les albums publics servis en direct, cette limite est assumée et documentée.
- **Vidéos + `images.level`.** Le hook `get_src_image_url` ne couvre que les images (`SrcImage::is_original()` filtre sur `$conf['picture_ext']`, qui exclut les vidéos). Une vidéo de niveau élevé dans un album **public** ne serait donc pas réécrite. L'accès aux vidéos d'albums **privés** reste protégé (le lien réel passe par `download_url` → `get_action_url()`). Non traité faute de cas d'usage identifié à ce jour.
