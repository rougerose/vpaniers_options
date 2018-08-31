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


/**
 * Calculer le total HT et TTC d'un panier. 
 * 
 * Les abonnements de soutien peuvent être à un prix déterminés 
 * par le souscripteur, la fonction automatique de calcul du prix 
 * du panier peut être faussée. 
 * 
 * @param  int $id_panier
 * @return array
 */
function filtre_panier_calcul_total($id_panier) {
	include_spip('inc/config');
	$tva = lire_config('vabonnements/taxe');
	$total_ht = 0;
	$total_ttc = 0;
	
	$items = sql_allfetsel('options, objet, id_objet, quantite', 'spip_paniers_liens', 'id_panier='.intval($id_panier));
	if ($items) {
		foreach ($items as $k => $item) {
			if ($item['objet'] == 'abonnements_offre') {
				$options = vpaniers_options_expliquer_options($item['options']);
				foreach ($options as $key => $option) {
					if ($option['prix_souscripteur']) {
						$total_ttc += $option['prix_souscripteur'] * 1;
						$total_ht += ($option['prix_souscripteur'] / (1 + $tva)) * 1;
					} else {
						$total_ht += prix_ht_objet($item['id_objet'], $item['objet']) * 1;
						$total_ttc += prix_objet($item['id_objet'], $item['objet']) * 1;
					}
				}
			} else {
				$total_ht += prix_ht_objet($item['id_objet'], $item['objet']) * $item['quantite'];
				$total_ttc += prix_objet($item['id_objet'], $item['objet']) * $item['quantite'];
			}
		}
		$total_ht = round($total_ht, 2);
		$total_ttc = round($total_ttc, 2);
		return array($total_ht, $total_ttc);
	}
}
