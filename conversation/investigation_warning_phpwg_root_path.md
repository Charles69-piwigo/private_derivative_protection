# Investigation — Warning `Constant PHPWG_ROOT_PATH already defined` dans `pdp_debug.log`

**Date :** 2026-07-08

## Contexte

`pdp_debug.log` affiche, à chaque délégation de dérivé protégé, une paire de lignes répétée :

```
PDP DELEGATING TO i.php : /galleries/Piwigo/.../xxx-cu_e520x360.png
PHP Warning:  Constant PHPWG_ROOT_PATH already defined in /volume1/web/photodev2/i.php on line 9
```

## Recherche du mécanisme core concerné

`grep -rn "function get_element_url\|trigger_change('get_element_url'\|trigger_event('get_element_url'\|render_element_content" include/ picture.php` :

- `include/functions_html.inc.php:620` — `get_element_url_protection_handler($url, $infos)` : handler **core natif** (pas du plugin pdp) qui redirige vers `action.php` via `get_action_url()` quand `original_url_protection` est actif.
- `include/functions_url.inc.php:783` — `function get_element_url($element_info)` : fonction core de génération d'URL.
- `picture.php:125` — `add_event_handler('render_element_content', 'default_picture_content')`.
- `picture.php:969` — `trigger_change('render_element_content', ...)`.

Confirmation : le mécanisme de protection des URLs d'éléments (`get_element_url_protection_handler`) est **natif au core Piwigo**, indépendant du plugin pdp — cohérent avec la mémoire projet sur le double hook `get_src_image_url` / `get_original_url`.

## Origine du warning

`plugins/private_derivative_protection/protect.php` :

```php
// ligne 15-16
chdir(realpath(__DIR__ . '/../..'));
define('PHPWG_ROOT_PATH', './');
...
// ligne 84-86
error_log('PDP DELEGATING TO i.php : '.$_SERVER['QUERY_STRING']);
include(PHPWG_ROOT_PATH.'i.php');
```

`i.php` (core, racine Piwigo), ligne 9 :

```php
define('PHPWG_ROOT_PATH','./');
```

`i.php` est conçu pour être appelé en entrée HTTP directe (process frais, constante pas encore définie). Ici, `protect.php` l'inclut depuis un contexte **déjà bootstrappé**, où `PHPWG_ROOT_PATH` est déjà définie — d'où le warning "already defined".

## Verdict : cosmétique, sans impact fonctionnel

Grâce au `chdir(realpath(__DIR__.'/../..'))` fait par `protect.php` avant sa propre définition, `'./'` pointe déjà sur la racine Piwigo au moment où `i.php` tente de redéfinir la constante. La valeur est **strictement identique** dans les deux définitions. PHP ignore la redéfinition et émet un warning, mais `i.php` continue de s'exécuter normalement avec le bon `PHPWG_ROOT_PATH`.

**Pas un bug à corriger.** Pour faire taire le warning il faudrait patcher `i.php` (`defined('PHPWG_ROOT_PATH') or define(...)`), mais c'est un fichier **core Piwigo** — un tel patch serait écrasé à la prochaine mise à jour du core. Non prioritaire.
