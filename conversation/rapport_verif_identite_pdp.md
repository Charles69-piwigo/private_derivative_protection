# Rapport — Vérification d'identité des installations `private_derivative_protection` (dev v1.2 · prod)

**Date :** 2026-07-08
**Racines comparées :**
- PROD : `photo.charles` (base `photo_charles`)
- DEV : `photodev2` (base `photodev2`), au commit `8c0f337` (post v1.2)

## Résultat

| Vérification | PROD | DEV | Verdict |
|---|---|---|---|
| Fichiers `.php` du plugin (Étape 1) | — | — | ✅ identiques (3 fichiers différaient en hash SHA-256 mais uniquement à cause de fins de ligne CRLF vs LF ; contenu strictement identique) |
| `i.php` racine (Étape 2) | `588940c8069f3bab0e3fde80185712a9e1b393bba22813b1eb73d7e95061f741` | `588940c8069f3bab0e3fde80185712a9e1b393bba22813b1eb73d7e95061f741` | ✅ identique |
| Config effective (Étape 3) | `original_url_protection` = `all` (DB) ; autres clés absentes de la DB et commentées en fichier → défauts core | idem | ✅ identique |
| Version core Piwigo (Étape 4) | 16.4.0 | 16.4.0 | ✅ identique |
| **Version PHP servie (Étape 4)** | **8.4.14** | **8.2.28** | ❌ **DIFFÉRENT** |
| Activation plugin (Étape 5) | actif, bonne version | actif, bonne version | ✅ identique |
| `.htaccess` `galleries/` + `upload/` (Étape 5) | présents, `Require all denied` OK | présents, `Require all denied` OK | ✅ identique |

## Conclusion

**Les installations ne sont pas identiques.** Six lignes sur sept concordent strictement : code du plugin, `i.php`, config Piwigo (fichier + DB), core Piwigo, activation du plugin, et `.htaccess`.

La seule divergence identifiée est la **version PHP** : PROD tourne sous **PHP 8.4.14**, DEV sous **PHP 8.2.28** — un écart de deux versions majeures.

## Piste à explorer

PHP 8.3 et 8.4 ont introduit plusieurs changements de comportement (dépréciations sur les propriétés dynamiques, changements sur `mysqli`, sérialisation, comparaisons implicites, etc.) susceptibles d'affecter le plugin sans que son code n'ait changé.

Prochaine étape suggérée : consulter le changelog PHP 8.3 → 8.4 pour tout changement touchant `mysqli`, sessions, ou manipulation de chaînes/URL — ou, plus direct, tester le plugin sous PHP 8.4 en dev (si le pool PHP-FPM de `photodev2` peut être temporairement basculé) pour confirmer que c'est bien la cause du comportement observé en prod.

## Méthodologie

Vérifications effectuées en lecture seule (checksums SHA-256, lecture de fichiers de config, requêtes SQL `SELECT` exécutées manuellement par Charles via phpMyAdmin). Aucune modification apportée aux deux installations. Basé sur la passation `passation_verif_identite_pdp.md`.
