<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function vpaniers_options_declarer_champs_extras($champs = array()) {
	$champs['spip_paniers_liens']['options'] = array(
		'saisie' => 'input',
		'options' => array(
			'nom' => 'options',
			'label' => _T('vpaniers_options:options_titre'),
			'sql' => "text NOT NULL DEFAULT ''"
		)
	);
	return $champs;
}
