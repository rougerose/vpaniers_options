<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}


function vpaniers_options_remplir_abonnements($id_auteur, $id_commande) {
	$id_commande = intval($id_commande);
	$id_auteur = intval($id_auteur);
	
	if ($id_auteur and $id_commande and $abonnements = sql_allfetsel('*', 'spip_commandes_details', 'id_commande='.$id_commande.' AND objet='.sql_quote('abonnements_offre'))) {
		
		$ids_abonnement = array();
		
		$champs = array(
			'id_auteur' => $id_auteur,
			'id_commande' => $id_commande
		);
		
		include_spip('action/editer_abonnement');
		
		foreach ($abonnements as $abonnement) {
			$options = vpaniers_options_expliquer_options($abonnement['options']);
			$options = $options[0];
			
			$champs['id_abonnements_offre'] = $abonnement['id_objet'];
			$champs['prix_echeance'] = $abonnement['prix_unitaire_ht'];
			$champs['numero_debut'] = $options['numero_debut'];
			
			if (strlen($options['nom_inscription'])) {
				$champs['offert'] = 'oui';
				
				// récupérer toutes les autres données de l'abonnement.
				foreach ($options as $cle => $option) {
					$champs[$cle] = $option;
				}
			}
			
			$id_abonnement = abonnement_inserer($id_parent = null, $champs);
			
			if (!$id_abonnement) {
				return '';
			} else {
				$ids_abonnement[] = $id_abonnement;
			}
		}
	}
	
	/*
	if ($abonnements = sql_allfetsel('*', 'spip_commandes_details', 'id_commande='.intval($id_commande).' AND objet='.sql_quote('abonnements_offre'))) {
			
			$champs = array(
				'id_auteur' => $commande['id_auteur'],
				'id_commande' => intval($id_commande),
			);
			
			include_spip('action/editer_abonnement');
			
			foreach ($abonnements as $abonnement) {
				$options = vpaniers_options_expliquer_options($abonnement['options']);
				
				$options = $options[0];
				
				$champs['id_abonnements_offre'] = $abonnement['id_objet'];
				$champs['prix_echeance'] = $abonnement['prix_unitaire_ht'];
				$champs['numero_debut'] = $options['numero_debut'];
				
				if (strlen($options['nom_inscription'])) {
					$champs['offert'] = 'oui';
					
					foreach ($options as $cle => $option) {
						$champs[$cle] = $option;
					}
				}
				
				$id_abonnement = abonnement_inserer($id_parent = null, $champs);
				
				if (!$id_abonnement) {
					spip_log("Erreur de création d'un abonnement pour la commande $id_commande, données de l'abonnement :".var_export($champs, true), 'vabonnements_commande'._LOG_ERREUR);
				}
			}
		}
	*/
}
