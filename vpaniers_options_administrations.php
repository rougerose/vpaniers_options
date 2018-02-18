<?php

/**
 * Installation et désinstallation du plugin 
 * 
 */

if (!defined('_ECRIRE_INC_VERSION')) {
  return;
}

include_spip('inc/cextras');
include_spip('base/vpaniers_options');

/**
 * Installation et mise à jour du plugin
 * @param  string $nom_meta_base_version
 * @param  string $version_cible
 * @return void
 */
function vpaniers_options_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();
	// ajouter champs extras
	cextras_api_upgrade(vpaniers_options_declarer_champs_extras(), $maj['create']);
	
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Désinstallation du plugin
 * @param  string $nom_meta_base_version
 * @return void
 */
function vpaniers_options_vider_tables($nom_meta_base_version) {
	cextras_api_vider_tables(vpaniers_options_declarer_champs_extras());
	effacer_meta($nom_meta_base_version);
}
