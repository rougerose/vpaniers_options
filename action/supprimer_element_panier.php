<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

/**
 * Supprimer un élément du panier
 *
 * Surcharge de la fonction du plugin Paniers
 * 
 * @param  [type] $arg [description]
 * @return [type]      [description]
 */
function action_supprimer_element_panier_dist($arg=null) {
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	
	// 
	// Surcharge : Ajout de la clé du tableau des options. Utilisée pour les abonnements.
	// 
	@list($objet, $id_objet, $cle) = explode('-', $arg);
	
	// Il faut cherche le panier du visiteur en cours
	include_spip('inc/paniers');
	
	$id_panier_base = 0;
	
	if ($id_panier = paniers_id_panier_encours()){
		// est-ce que le panier est bien en base
		$id_panier_base = intval(sql_getfetsel(
			'id_panier',
			'spip_paniers',
			array(
				'id_panier = '.intval($id_panier),
				'statut = '.sql_quote('encours')
			)
		));
	}
	
	// S'il n'y a pas de panier, on ne fait rien
	if (!$id_panier OR !$id_panier_base) {
		return false;
	}
	
	// 
	// Début Surcharge
	// 
	
	if ($objet == 'abonnements_offre' and isset($cle) and $abonnement = sql_fetsel('options, quantite', 'spip_paniers_liens', 'id_panier='.intval($id_panier).' and objet='.sql_quote($objet).' and id_objet='.intval($id_objet))) {
		
		$options = vpaniers_options_expliquer_options($abonnement['options']);
		$quantite = $abonnement['quantite'] - 1;
		
		// S'il reste d'autres abonnements lié à l'objet, 
		// il faut seulement supprimer les options et mettre à jour la quantité
		if ($quantite > 0) {
			unset($options[$cle]);
			$options = vpaniers_options_produire_options($options);
			sql_updateq(
				'spip_paniers_liens', 
				array('quantite' => $quantite, 'options' => $options), 
				'id_panier='.intval($id_panier).' and objet='.sql_quote($objet).' and id_objet='.intval($id_objet));
		}
	}
	
	if ($objet != 'abonnements_offre' or ($objet == 'abonnements_offre' and $quantite == 0)) {
		$t = 'faire un truc';
		// Suppression de l'objet
		sql_delete(
			'spip_paniers_liens',
			array(
				'id_panier = '.intval($id_panier),
				'objet = '.sql_quote($objet),
				'id_objet = '.intval($id_objet)
			)
		);
	}
	
	sql_updateq('spip_paniers', array('date'=>date('Y-m-d H:i:s')), 'id_panier = '.intval($id_panier));
	
	// 
	// Fin surcharge
	// 
	
	include_spip('inc/invalideur');
	suivre_invalideur("id='$objet/$id_objet'");
}
