<?php

if (!defined("_ECRIRE_INC_VERSION")) {
    return;
}

/**
 * Convertir un timestamp unix vers une date
 * qui peut-être formatée par Spip au moyen du filtre |affdate
 * 
 * @param  string $timestamp
 * @return string date Y-m-d
 */
function vpaniers_timestamp_vers_date($timestamp) {
	return $date = date('Y-m-d', $timestamp);
}


/**
 * Calculer un hash de sécurité pour le paiement d'une commande. 
 * 
 * @param  int $id_auteur
 * @param  int $id_commande
 * @param  int $date_commande
 * @return string
 */
function vpaniers_calcul_hash_commande($id_auteur, $id_commande, $date_commande) {
	$donnees = array($id_auteur, $id_commande, $date_commande);
	return md5(implode(';', array_values($donnees)));
}
