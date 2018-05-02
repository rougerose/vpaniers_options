<?php

if (!defined("_ECRIRE_INC_VERSION")) {
    return;
}


function formulaires_valider_panier_charger_dist($id_panier, $garder_panier=false, $redirect=""){
	if (!$id_panier OR !sql_countsel('spip_paniers','id_panier='.intval($id_panier)))
		return "<p>"._T('paniers:erreur_aucune_commande')."</p>";
	
	$valeurs = array(
		'id_auteur' => '',
		'id_panier' => $id_panier,
		'contrats' => array(),
	);

	return $valeurs;
}


function formulaires_valider_panier_verifier_dist($id_panier, $garder_panier=false, $redirect=""){
	$erreurs = array();
	
	$id_auteur = (_request('id_auteur')) ? _request('id_auteur') : 0;
	if ($id_auteur == 0) {
		$erreurs['message_erreur'] = _T('vpaniers:erreur_id_auteur');
	}
	$contrats = _request('contrats');
	if (!$contrats) {
		$contrats = array();
	}
	
	if (!in_array('cgv',$contrats)) {
		$erreurs['message_erreur'] = _T('vpaniers:erreur_accepter_contrat');
	}
	
	if (!$id_panier) {
		$erreurs['message_erreur'] = _T('vpaniers:erreur_id_panier');
	}
	
	return $erreurs;
}

function formulaires_valider_panier_traiter_dist($id_panier, $garder_panier=false, $redirect=""){
	
	include_spip('inc/commandes');
	include_spip('inc/config');
	// si une commande recente est encours (statut et dans la session de l'utilisateur), on la reutilise
	// plutot que de recreer N commandes pour un meme panier
	// (cas de l'utilisateur qui revient en arriere puis retourne a la commande)
	
	include_spip('inc/session');
	$id_commande = '';
	$id_auteur = _request('id_auteur');

	if ($session_id_commande = session_get('id_commande')) {
		$where = 'statut='.sql_quote('encours').' AND date>'.sql_quote(date('Y-m-d H:i:s', strtotime('-' . lire_config('paniers/limite_ephemere', 24) . ' hour'))).' AND source='.sql_quote("panier#$id_panier").' AND id_commande='.intval($session_id_commande);
	
		$id_commande = sql_getfetsel("id_commande", "spip_commandes", $where);
	}

	// sinon on cree une commande "en cours"
	if (!$id_commande) {
		$id_commande = creer_commande_encours();
	}

	// et la remplir les details de la commande d'après le panier en session
	if ($id_commande) {
		// NOTE: Compte tenu des traitements particuliers pour les abonnements
		// -- par exemple abonnement offert ou 2 abonnements identiques mais 
		// l'un est offert et l'autre pas -- il faut utiliser une fonction
		// spécifique et non la fonction panier2commande_remplir_commande.
		//
		// include_spip('action/commandes_paniers');
		// panier2commande_remplir_commande($id_commande, $id_panier, false);
		
		include_spip('inc/vpaniers_options_commande');
		vpaniers_options_vers_commande($id_commande, $id_panier, false);
		
		// Supprimer le panier ?
		if (!$garder_panier) {
			$supprimer_panier = charger_fonction('supprimer_panier_encours', 'action/');
			$supprimer_panier();
		}
		
		$res = array('message_ok' => _T('vpaniers:message_panier_valide'));
		
		if ($redirect) {
			// calculer un hash de sécurité pour le paiement de cette commande. 
			$date_commande = sql_getfetsel('date', 'spip_commandes', 'id_commande='.$id_commande);
			$hash = vpaniers_calcul_hash_commande($id_auteur, $id_commande, $date_commande);
			$res['redirect'] = parametre_url(parametre_url($redirect, 'id_commande', $id_commande), 'hash', $hash);
		}
	} else {
		spip_log("Echec de création de commande depuis le panier #$id_panier",'vpaniers.'. _LOG_CRITIQUE);
		$res = array('message_erreur' => _T('vpaniers:erreur_technique_creation_commande'));
	}
	
	return $res;
}
