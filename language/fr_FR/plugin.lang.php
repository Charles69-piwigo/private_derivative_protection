<?php
$lang['Private Derivative Protection'] = 'Private Derivative Protection';
$lang['État']                          = 'État';
$lang['Aide']                          = 'Aide';
$lang['Serveur']                       = 'Serveur';
$lang['Serveur compatible (Apache)']   = 'Serveur compatible (Apache)';
$lang['Serveur Nginx détecté : les fichiers .htaccess sont ignorés, la protection pdp n\'est pas active.']
    = 'Serveur Nginx détecté : les fichiers .htaccess sont ignorés, la protection pdp n\'est pas active.';
$lang['Protection des fichiers originaux'] = 'Protection des fichiers originaux';
$lang['Protection des dérivées']       = 'Protection des dérivées';
$lang['Actif']                         = 'Actif';
$lang['Absent ou incorrect']           = 'Absent ou incorrect';
$lang['Albums privés protégés']        = 'Albums privés protégés';
$lang['Aucun album privé, pdp n\'a rien à protéger.']
    = 'Aucun album privé, pdp n\'a rien à protéger.';
$lang['Gérer les plugins'] = 'Gérer les plugins';

$lang['pdp_nginx_global_warning']
    = 'Private Derivative Protection : serveur Nginx détecté, la protection des fichiers est inactive.'
    . ' Les photos des albums privés restent accessibles par accès direct.'
    . ' Désactivez le plugin pour supprimer ce message.';

$lang['pdp_nginx_config_warning']
    = 'Serveur Nginx détecté : la protection des fichiers est inactive.'
    . ' Les photos des albums privés restent accessibles par accès direct malgré le plugin.'
    . ' Désactivez le plugin pour supprimer ce message.';

// ── Onglet Aide ────────────────────────────────────────────────────────────

$lang['pdp_help_tagline']
    = 'Protège les albums privés Piwigo contre l\'accès direct à leurs photos par URL, sans passer par les vérifications de droits habituelles de Piwigo.';

$lang['pdp_help_problem_title'] = 'Le problème que pdp résout';

$lang['pdp_help_problem_p1']
    = 'Piwigo vérifie les droits d\'accès quand on <strong>navigue</strong> normalement sur le site : un invité ne voit pas les albums privés dans les listes, ni les vignettes, ni les photos en grand format.';

$lang['pdp_help_problem_p2']
    = 'Mais ce contrôle porte sur la navigation, pas sur le fichier lui-même. Piwigo génère et met en cache des fichiers dérivés (miniatures, formats d\'affichage) sur le disque, à un emplacement prévisible. Si quelqu\'un connaît ou devine l\'URL directe d\'un de ces fichiers, il peut y accéder — même si la photo appartient à un album privé — sans jamais passer par une page Piwigo qui aurait vérifié ses droits. Il en va de même pour les fichiers originaux (pleine résolution).';

$lang['pdp_help_problem_p3']
    = 'Ce n\'est pas un bug de Piwigo : c\'est une limitation d\'architecture connue et documentée par le projet lui-même (voir les tickets liés sur le forum et GitHub). pdp la referme.';

$lang['pdp_help_protects_title'] = 'Ce que pdp protège, et comment';

$lang['pdp_help_derivatives_title'] = 'Les dérivés (miniatures et formats d\'affichage)';

$lang['pdp_help_derivatives_p1']
    = 'Pour toute photo appartenant à un album privé (ou de niveau d\'accès élevé), pdp remplace les liens habituels vers le fichier en cache par une URL signée (jeton HMAC, durée de vie limitée — 3 heures par défaut, paramétrable). Cette URL passe par un script (<code>protect.php</code>) qui revérifie les droits avant de servir l\'image.';

$lang['pdp_help_derivatives_p2']
    = 'Pour que cette protection ne puisse pas être contournée par un accès direct au fichier en cache, pdp force également <code>$conf[\'derivative_url_style\'] = 2</code> : <strong>toutes</strong> les miniatures, y compris celles des albums publics, transitent désormais par un script PHP plutôt que par un lien direct vers le fichier. Le dossier de cache des dérivés (<code>_data/i/</code>) est alors lui-même protégé par un <code>.htaccess</code>, sans rien casser côté albums publics puisque plus aucune page ne pointe directement dessus.';

$lang['pdp_help_derivatives_p3']
    = 'Les tests menés sur des albums publics de taille courante (jusqu\'à ~250 photos), y compris sous charge concurrente légère, n\'ont montré aucun impact de performance mesurable par rapport au mode d\'affichage direct habituel de Piwigo.';

$lang['pdp_help_originals_title'] = 'Les originaux (pleine résolution)';

$lang['pdp_help_originals_intro'] = 'Deux cas sont couverts, pour les images comme pour les vidéos et les documents PDF :';

$lang['pdp_help_originals_list']
    = '<li><strong>Le chemin natif de Piwigo</strong> (barre de tailles, téléchargement) : pdp s\'appuie sur le mécanisme natif du core (<code>action.php</code>) quand il est suffisant, et ne réécrit vers son propre script (<code>serve_original.php</code>) que lorsqu\'une vérification supplémentaire est nécessaire (album privé, ou niveau d\'accès élevé dans un album par ailleurs public).</li>'
    . '<li><strong>Les URLs construites directement par certains plugins tiers</strong> (par exemple <code>super_zoom</code>), qui contournent le mécanisme natif de génération d\'URL. Un <code>.htaccess</code> bloque l\'accès direct au fichier, et un point d\'accroche dédié route ces cas vers <code>serve_original.php</code>, qui revérifie les droits avant de servir le fichier.</li>';

$lang['pdp_help_originals_p2']
    = '<code>serve_original.php</code> sert aussi bien les images que les vidéos (avec prise en charge des requêtes <code>Range</code>, nécessaire au curseur de lecture des lecteurs vidéo) et les documents PDF affichés en pleine page.';

$lang['pdp_help_blocking_title'] = 'Blocage de l\'accès direct aux fichiers';

$lang['pdp_help_blocking_p']
    = 'En complément, pdp installe des règles <code>.htaccess</code> (<code>Require all denied</code>) sur les dossiers contenant les fichiers originaux, les fichiers en cours d\'import et le cache des dérivés (<code>galleries/</code>, <code>upload/</code>, <code>_data/i/</code>), pour empêcher tout accès direct qui contournerait les mécanismes ci-dessus.';

$lang['pdp_help_compat_title'] = 'Compatibilité';

$lang['pdp_help_compat_intro'] = 'Testé et confirmé compatible avec :';

$lang['pdp_help_compat_list']
    = '<li><code>question_mark_in_urls = false</code></li>'
    . '<li><code>category_url_style = id-name</code></li>'
    . '<li><code>picture_url_style = file</code></li>'
    . '<li>Le plugin ShareAlbum (liens de partage <code>?xauth=</code>)</li>'
    . '<li>Les plugins <code>super_zoom</code>, <code>castor</code>, <code>face_tag_editor</code>, <code>geo_tag_editor</code> (versions patchées, disponibles sur le dépôt officiel des plugins Piwigo)</li>';

$lang['pdp_help_compat_other']
    = 'Si vous utilisez un autre plugin qui construit ses propres URLs vers les fichiers originaux ou dérivés (plutôt que d\'utiliser les mécanismes standards de génération d\'URL de Piwigo), il pourrait ne pas être protégé automatiquement — signalez-le si vous en rencontrez un.';

$lang['pdp_help_install_title'] = 'Installation';

$lang['pdp_help_install_intro'] = 'L\'activation du plugin met en place automatiquement :';

$lang['pdp_help_install_list']
    = '<li>les règles <code>.htaccess</code> nécessaires (<code>galleries/</code>, <code>upload/</code>, <code>_data/i/</code>)</li>'
    . '<li>le paramètre <code>original_url_protection = \'all\'</code></li>'
    . '<li>le paramètre <code>derivative_url_style = 2</code></li>';

$lang['pdp_help_install_restore']
    = 'La désactivation ou désinstallation retire ces réglages proprement, et restaure l\'affichage direct habituel des miniatures.';

$lang['pdp_help_install_prereq']
    = '<strong>Prérequis serveur :</strong> Apache 2.4 avec <code>AllowOverride</code> autorisant les fichiers <code>.htaccess</code> sur les dossiers concernés. <code>mod_rewrite</code> n\'est pas nécessaire.';

$lang['pdp_help_limitations_title'] = 'Limitations connues';

$lang['pdp_help_limitations_intro']
    = 'Ce plugin ferme une faille réelle. La limitation ci-dessous n\'existe pas dans son fonctionnement par défaut, mais mérite d\'être comprise pour saisir ce que le réglage automatique <code>derivative_url_style = 2</code> apporte concrètement.';

$lang['pdp_help_cache_title'] = 'Pourquoi le dossier de cache des dérivés est entièrement bloqué';

$lang['pdp_help_cache_p1']
    = 'Piwigo ne génère qu\'un nombre restreint de formats de taille par photo (vignette, moyen, grand, très grand, formats spécifiques à certains plugins d\'affichage). Dès qu\'<strong>un seul</strong> de ces formats a été généré pour une photo donnée — quelle qu\'en soit la raison (aperçu au moment de l\'import, consultation via l\'interface d\'administration, affichage dans un album, etc.) — son chemin sur disque devient prévisible.';

$lang['pdp_help_cache_p2']
    = 'Sans intervention, un tel chemin resterait accessible en accès direct s\'il est connu ou deviné, même si aucune page du site ne le divulgue plus. C\'est pour fermer précisément ce cas que pdp ne se contente pas de masquer les liens : il force <code>derivative_url_style = 2</code> afin que plus aucune miniature, publique ou privée, ne soit jamais servie par un accès direct au fichier — ce qui permet de bloquer tout le dossier <code>_data/i/</code> par <code>.htaccess</code> sans casser l\'affichage des albums publics.';

$lang['pdp_help_nginx_title'] = 'Fonctionnement sous nginx';

$lang['pdp_help_nginx_p1']
    = 'Sous nginx, les fichiers <code>.htaccess</code> ne sont pas lus — c\'est une caractéristique de ce serveur, pas une limitation de pdp. Le blocage de l\'accès direct aux fichiers ne fonctionne donc pas automatiquement dans cette configuration : sans Apache pour appliquer un <code>.htaccess</code>, cette couche de protection est tout simplement inopérante.';

$lang['pdp_help_nginx_p2']
    = 'Ce qui continue de fonctionner malgré tout : les mécanismes côté PHP (<code>action.php</code> du core, <code>serve_original.php</code> et <code>protect.php</code> de pdp) restent actifs quel que soit le serveur, puisqu\'ils ne dépendent pas du <code>.htaccess</code>. Ce qui disparaît sous nginx, c\'est uniquement la couche de blocage direct des fichiers en complément.';

$lang['pdp_help_nginx_p3']
    = 'pdp détecte automatiquement l\'environnement (Apache pur, nginx pur, ou configuration hybride Apache + nginx en proxy) et affiche le résultat de cette détection sur sa page de configuration dans l\'administration, avec un bandeau d\'avertissement si nginx est identifié.';
