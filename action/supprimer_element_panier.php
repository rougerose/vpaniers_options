<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}


/**
 * Supprimer un article du panier en cours. 
 *
 * Cette fonction vient surcharger celle du plugin Paniers,
 * afin d'ajouter un traitement spécifique des abonnements offerts.
 * 
 * @param  string $arg objet-id_objet-cle 
 * @return bool|void
 */
function action_supprimer_element_panier($arg=null) {
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// 
	// On récupère l'objet à supprimer du panier.
	// Les données sont formatées ainsi : 
	// objet-id_objet-cle
	// 
	@list($objet, $id_objet, $cle) = explode('-', $arg);

	// 
	// Il faut chercher le panier du visiteur en cours
	// 
	include_spip('inc/paniers');
	
	$id_panier_base = 0;
	
	if ($id_panier = paniers_id_panier_encours()) {
		// 
		// Est-ce que le panier est bien en base
		// 
		$id_panier_base = intval(sql_getfetsel(
			'id_panier',
			'spip_paniers',
			array(
				'id_panier = '.intval($id_panier),
				'statut = '.sql_quote('encours')
			)
		));
	}

	// 
	// S'il n'y a pas de panier, on ne fait rien
	// 
	if (!$id_panier OR !$id_panier_base) {
		return false;
	}
	
	// 
	// Si l'objet est un abonnement, il y a des traitements spécifiques.
	// Sinon, on supprime simplement l'objet du panier.
	// 
	if ($objet == 'abonnements_offre') {
		//
		// L'objet est déjà dans la base ?
		// 
		$deja = sql_fetsel(
			'options, quantite',
			'spip_paniers_liens',
			'id_panier='.intval($id_panier).' and objet='.sql_quote($objet).' and id_objet='.intval($id_objet)
		);
		
		$options = unserialize($deja['options']);
		$options_item = $options[$cle];
		
		// 
		// Si l'abonnement à supprimer est un abonnement offert, 
		// des traitements spécifiques sont nécessaires.
		// 
		// Si le champ Options de l'abonnement à supprimer contient un "coupon" 
		// avec un nombre, il s'agit de l'id_auteur du bénéficiaire de l'abonnement offert.
		// On met en statut poubelle le profil Auteur et l'éventuel message
		// à son attention.
		// 
		if (is_numeric($options_item[0])) {
			$id_auteur = intval($options_item[0]);
			
			// 
			// Le message éventuel à son attention est mis à la poubelle
			// 
			if ($id_message = sql_getfetsel('id_message', 'spip_messages', 'destinataires='.$id_auteur.' and statut='.sql_quote('prepa').' and type='.sql_quote('kdo'))) {
				sql_updateq('spip_messages', array('statut' => 'poub'), 'id_message='.intval($id_message));
			}
			
			// 
			// L'auteur a été créé récemment (statut=nouveau) ? 
			// Si c'est le cas, on peut dissocier contact, organisation et adresse
			// et le mettre à la poubelle.
			// 
			if (sql_countsel('spip_auteurs', 'id_auteur='.$id_auteur.' and statut='.sql_quote('nouveau'))) {
				$id_contact = sql_getfetsel('id_contact', 'spip_contacts', 'id_auteur='.intval($id_auteur));
				
				include_spip('inc/autoriser');
				// 
				// Organisation liée ?
				// 
				if ($id_organisation = sql_getfetsel('id_organisation', 'spip_organisations_liens', 'id_objet='.$id_contact.' and objet='.sql_quote('contact'))) {
					autoriser_exception('supprimer', 'organisation', $id_organisation);
					$dissocier = charger_fonction("supprimer_lien","action");
					$organisation_contact = "organisation-$id_organisation-contact-$id_contact";
					$dissocier($organisation_contact);
					autoriser_exception('supprimer', 'organisation', $id_organisation, false);
				}
				
				// 
				// Adresse liée ?
				// 
				if ($id_adresse = sql_getfetsel('id_adresse', 'spip_adresses_liens', 'id_objet='.intval($id_auteur).' AND objet='.sql_quote('auteur').' AND type='.sql_quote(_ADRESSE_TYPE_DEFAUT))) {
					autoriser_exception('modifier', 'auteur', $id_auteur);
					$dissocier_adresse = charger_fonction("dissocier_adresse","action");
					$dissocier_adresse(intval($id_adresse)."/auteur/$id_auteur");
					autoriser_exception('modifier', 'auteur', $id_auteur, false);
				}
				
				// 
				// Le contact associé
				// 
				$dissocier_contact = charger_fonction("lier_contact_auteur","action");
				$dissocier_contact("$id_contact/0");
				
				// 
				// Auteur à la poubelle et supprimer son mail.
				// 
				autoriser_exception('modifier', 'auteur', $id_auteur);
				include_spip('action/editer_auteur');
				auteur_modifier($id_auteur, array('statut' => '5poubelle', 'email' => ''));
				autoriser_exception('modifier', 'auteur', $id_auteur, false);
			}
			
		}
		
		// 
		// S'il y a qu'un seul objet dans le panier, on le supprime.
		// Sinon on supprime uniquement l'item dans le tableau des Options
		// et on modifie la quantité en conséquence.
		// 
		if ($deja['quantite'] == 1) {
			sql_delete(
				'spip_paniers_liens',
				array(
					'id_panier = '.intval($id_panier),
					'objet = '.sql_quote($objet),
					'id_objet = '.intval($id_objet)
				)
			);
			
		} else {
			$quantite = -1;
			
			$paniers_arrondir_quantite = charger_fonction('paniers_arrondir_quantite', 'inc');
			$cumul_quantite = $paniers_arrondir_quantite($deja['quantite'] + $quantite, $objet, $id_objet);
			
			array_splice($options, $cle, 1);
			$options = serialize($options);
			
			sql_updateq(
				'spip_paniers_liens',
				array('options' => $options, 'quantite' => $cumul_quantite),
				'id_panier = ' . intval($id_panier) . ' and objet = ' . sql_quote($objet) . ' and id_objet = ' . intval($id_objet)
			);
		}
	} else {
		sql_delete(
			'spip_paniers_liens',
			array(
				'id_panier = '.intval($id_panier),
				'objet = '.sql_quote($objet),
				'id_objet = '.intval($id_objet)
			)
		);
	}
	
	//
	// Mise à jour de la date du panier
	// 
	sql_updateq(
		'spip_paniers',
		array('date'=>date('Y-m-d H:i:s')),
		'id_panier = '.intval($id_panier)
	);
	
	// Mise à jour des caches
	// 
	include_spip('inc/invalideur');
	suivre_invalideur("id='$objet/$id_objet'");
}
