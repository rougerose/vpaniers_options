<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}


/**
 * Supprimer un article du panier en cours. 
 *
 * Cette fonction vient surcharger celle du plugin Paniers,
 * afin du traitement spécifique des abonnements offerts.
 * 
 * @param  string $arg objet-id_objet 
 * @return bool|void
 */
function action_supprimer_element_panier($arg=null) {
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// On récupère l'objet à supprimer du panier
	@list($objet, $id_objet) = explode('-', $arg);

	// Il faut cherche le panier du visiteur en cours
	include_spip('inc/paniers');
	$id_panier_base = 0;
	if ($id_panier = paniers_id_panier_encours()){
		//est-ce que le panier est bien en base
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
	
	// Spécifique Abonnements Vacarme
	// 
	if ($objet == 'abonnements_offre') {
		// 
		// Si le champ Option contient un "coupon" avec un nombre,
		// il s'agit de l'id_auteur du bénéficiaire de l'abonnement offert.
		// On met en statut poubelle le profil Auteur et l'éventuel message
		// à son attention.
		// 
		$options = sql_getfetsel(
			'options',
			'spip_paniers_liens',
			'id_panier='.intval($id_panier).' and objet='.sql_quote($objet).' and id_objet='.intval($id_objet)
		);
		
		$options = unserialize($options);
		
		if (is_numeric($options['coupon'])) {
			$id_auteur = intval($options['coupon']);
			
			// Le message éventuel à son attention est mis à la poubelle
			if ($id_message = sql_getfetsel('id_message', 'spip_messages', 'destinataires='.$id_auteur.' and statut='.sql_quote('prepa').' and type='.sql_quote('kdo'))) {
				sql_updateq('spip_messages', array('statut' => 'poub'), 'id_message='.intval($id_message));
			}
			
			// l'auteur a été créé récemment (statut=nouveau) ? 
			// Si c'est le cas, on peut dissocier contact, organisation et adresse
			// et le mettre à la poubelle.
			if (sql_countsel('spip_auteurs', 'id_auteur='.$id_auteur.' and statut='.sql_quote('nouveau'))) {
				$id_contact = sql_getfetsel('id_contact', 'spip_contacts', 'id_auteur='.intval($id_auteur));
				
				// Organisation liée ?
				if ($id_organisation = sql_getfetsel('id_organisation', 'spip_organisations_liens', 'id_objet='.$id_contact.' and objet='.sql_quote('contact'))) {
					$dissocier = charger_fonction("supprimer_lien","action");
					$organisation_contact = "organisation-$id_organisation-contact-$id_contact";
					$dissocier($organisation_contact);
				}
				
				// Adresse liée ?
				if ($id_adresse = sql_getfetsel('id_adresse', 'spip_adresses_liens', 'id_objet='.intval($id_auteur).' AND objet='.sql_quote('auteur').' AND type='.sql_quote(_ADRESSE_TYPE_DEFAUT))) {
					$dissocier_adresse = charger_fonction("dissocier_adresse","action");
					$dissocier_adresse(intval($id_adresse)."/auteur/$id_auteur");
				}
				
				// Le contact associé
				$dissocier_contact = charger_fonction("lier_contact_auteur","action");
				$dissocier_contact("$id_contact/0");
				
				// Auteur à la poubelle
				include_spip('inc/autoriser');
				autoriser_exception('modifier', 'auteur', $id_auteur);
				
				include_spip('action/editer_auteur');
				auteur_modifier($id_auteur, array('statut' => '5poubelle'));
				
				// retirer l'autorisation exceptionnelle
				autoriser_exception('modifier', 'auteur', $id_auteur, false);

			}
		}
	}
	
	// On supprime l'objet du panier
	sql_delete(
		'spip_paniers_liens',
		array(
			'id_panier = '.intval($id_panier),
			'objet = '.sql_quote($objet),
			'id_objet = '.intval($id_objet)
		)
	);

	include_spip('inc/invalideur');
	suivre_invalideur("id='$objet/$id_objet'");
}
