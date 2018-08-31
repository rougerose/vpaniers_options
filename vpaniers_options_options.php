<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}


/**
 * Serialize les options d'un abonnement
 *
 * fonction reprise de Paniers https://github.com/nursit/paniers
 * 
 * @param  array $items
 * @return string
 */
function vpaniers_options_produire_options($items) {
	$articles = array();
	
	foreach ($items as $key => $item) {
		$data = array(
			$item['numero_debut'],
			$item['cadeau'],
			$item['prix_souscripteur'],
			$item['civilite'],
			$item['nom_inscription'],
			$item['prenom'],
			$item['mail_inscription'],
			$item['organisation'],
			$item['service'],
			$item['voie'],
			$item['complement'],
			$item['boite_postale'],
			$item['code_postal'],
			$item['ville'],
			$item['region'],
			$item['pays'],
			$item['message'],
			$item['date_message']
		);
		$item = implode('|', array_map('urlencode', $data));
		$articles[] = $item;
	}
	
	$options = implode('!', $articles);
	
	return $options;
}


/**
 * Deserialize les options d'un abonnement
 *
 * fonction reprise de Paniers https://github.com/nursit/paniers
 * 
 * @param  string $options
 * @return array
 */
function vpaniers_options_expliquer_options($options) {
	$articles = explode('!', $options);
	$opts = array();
	
	foreach ($articles as $k => $article) {
		if (strlen(trim($article))) {
			$articles[$k] = array_map('urldecode', explode('|', $article));
			$opts[$k]['numero_debut'] = $articles[$k][0];
			$opts[$k]['cadeau'] = $articles[$k][1];
			$opts[$k]['prix_souscripteur'] = $articles[$k][2];
			$opts[$k]['civilite'] = $articles[$k][3];
			$opts[$k]['nom_inscription'] = $articles[$k][4];
			$opts[$k]['prenom'] = $articles[$k][5];
			$opts[$k]['mail_inscription'] = $articles[$k][6];
			$opts[$k]['organisation'] = $articles[$k][7];
			$opts[$k]['service'] = $articles[$k][8];
			$opts[$k]['voie'] = $articles[$k][9];
			$opts[$k]['complement'] = $articles[$k][10];
			$opts[$k]['boite_postale'] = $articles[$k][11];
			$opts[$k]['code_postal'] = $articles[$k][12];
			$opts[$k]['ville'] = $articles[$k][13];
			$opts[$k]['region'] = $articles[$k][14];
			$opts[$k]['pays'] = $articles[$k][15];
			$opts[$k]['message'] = $articles[$k][16];
			$opts[$k]['date_message'] = $articles[$k][17];
		} else {
			unset($articles[$k]);
		}
	}
	return $opts;
}
