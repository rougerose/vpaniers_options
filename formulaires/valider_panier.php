<?php 

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}


function formulaires_valider_panier_charger_dist($id_panier, $supprimer = false) {
	if (!$id_panier OR !sql_countsel('spip_paniers','id_panier='.intval($id_panier)) OR !sql_countsel('spip_paniers_liens', 'id_panier='.intval($id_panier))) {
		$panier_vide = recuperer_fond('inclure/panier-vide', array('panier' => 'vide'));
		return $panier_vide;
	}
	
	if (!isset($GLOBALS["visiteur_session"]['statut']) AND !$GLOBALS["visiteur_session"]['statut']) {
		$valeurs['editable'] = false;
	}
	
	$valeurs['id_panier'] = $id_panier;
	$valeurs['id_auteur'] = '';
	
	return $valeurs;
}



function formulaires_valider_panier_verifier_dist($id_panier, $supprimer = false) {
	$erreurs = array();
	
	if (!$id_auteur = intval(_request('id_auteur'))) {
		$erreurs['message_erreur'] = _T('vpaniers:erreur_id_auteur');
	}
	
	if (!$id_panier) {
		$erreurs['message_erreur'] = _T('vpaniers:erreur_id_panier');
	}
	
	return $erreurs;
}



function formulaires_valider_panier_traiter_dist($id_panier, $supprimer = false) {
	$res = array();
	
	if (_request('supprimer')) {
		
		$supprimer_panier = charger_fonction("supprimer_panier","action");
		$supprimer_panier($id_panier);
	
	} else {
		
		// 
		// Ce qui suit est repris de action_commandes_paniers_dist
		// 
		include_spip('inc/commandes');
		include_spip('inc/config');
		include_spip('inc/session');
		include_spip('inc/vpaniers_options_remplir_commande');
		
		$id_auteur = _request('id_auteur');
		
		// Si une commande récente est en cours 
		// (statut et dans la session de l'utilisateur), 
		// on la réutilise plutot que de recréer N commandes pour un même panier
		// (cas de l'utilisateur qui revient en arrière puis retourne à la commande)
		
		$id_commande = sql_getfetsel(
			"id_commande",
			"spip_commandes", 
			"statut=".sql_quote('encours')
				." AND date>".sql_quote(date('Y-m-d H:i:s', strtotime('-'.lire_config('paniers/limite_ephemere', 24).' hour')))
				." AND source=".sql_quote("panier#$id_panier")
				." AND id_commande=".session_get('id_commande')
		);
		
		// Créer une nouvelle commande
		if (!$id_commande) {
			$id_commande = creer_commande_encours();
		}
		
		// Et la remplir avec le panier
		if ($id_commande) {
			$remplir_commande = vpaniers_options_remplir_commande($id_auteur, $id_commande, $id_panier, false);
			$res['message_ok'] = _T('vpaniers:message_panier_valide');
			$res['redirect'] = generer_url_public('commande', 'id_commande=' . $id_commande, true);
		} else {
			$res['message_erreur'] = _T('vpaniers:erreur_technique_creation_commande');
		}
	}
	return $res;
}
