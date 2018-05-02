<?php

if (!defined("_ECRIRE_INC_VERSION")) {
    return;
}

/**
 * Remplir une commande d'après un panier. 
 *
 * Dérivée de la fonction panier2commande_remplir_commande du plugin Paniers
 * pour permettre les traitements spécifiques aux abonnements, notamment
 * le fait de dédoubler des lignes d'offres abonnements afin d'en faire
 * des lignes spécifiques car les valeurs d'options sont spécifiques à chaque
 * abonnement.
 *
 * @param  int  $id_commande
 * @param  int  $id_panier
 * @param  boolean $append  true pour ajouter brutalement le panier 
 * a la commande, false pour verifier que commande==panier 
 * en ajoutant/supprimant uniquement les details necessaires.
 * @return void
 */
function vpaniers_options_vers_commande($id_commande, $id_panier, $append = true) {
	include_spip('action/editer_objet');
	include_spip('inc/filtres');
	include_spip('inc/paniers');
	
	// noter le panier source dans le champ source de la commande
	objet_modifier('commande', $id_commande, array('source' => "panier#$id_panier"));

	// recopier le contenu du panier dans la commande
	// On récupère le contenu du panier
	$panier = sql_allfetsel(
			'*', 'spip_paniers_liens', 'id_panier = ' . intval($id_panier)
	);

	// Pour chaque élément du panier, on va remplir la commande
	// (ou verifier que la ligne est deja dans la commande)
	if ($panier and is_array($panier)) {
		
		$details = array();
		
		include_spip('spip_bonux_fonctions');
		$fonction_prix = charger_fonction('prix', 'inc/');
		$fonction_prix_ht = charger_fonction('ht', 'inc/prix');
		
		foreach ($panier as $emplette) {
			$prix_ht = $fonction_prix_ht($emplette['objet'], $emplette['id_objet'], 6);
			$prix = $fonction_prix($emplette['objet'], $emplette['id_objet'], 6);

			// On déclenche un pipeline pour pouvoir éditer le prix avant la création de la commande
			// Utile par exemple pour appliquer une réduction automatique lorsque la commande est crée
			// $prix_pipeline = pipeline(
			// 	'panier_vers_commande_prix',
			// 	array(
			// 		'args' => $emplette,
			// 		'data' => array(
			// 			'prix' => $prix,
			// 			'prix_ht' => $prix_ht
			// 		)
			// 	)
			// );

			// On ne récupère que le prix_ht dans le pipeline
			// $prix_ht = $prix_pipeline['prix_ht'];
			// $prix = $prix_pipeline['prix'];

			if ($prix_ht > 0)
				$taxe = round(($prix - $prix_ht) / $prix_ht, 6);
			else
				$taxe = 0;
			
			$items_set = array();
			
			// 
			// Est-ce un abonnement ?
			// 
			if ($emplette['objet'] == 'abonnements_offre') {
				
				$options = unserialize($emplette['options']);
				$quantite = $emplette['quantite'];
				// $items_set = array();
				
				foreach ($options as $opts) {
					//
					// Abonnement offert ?
					// Sinon c'est un abonnement normal.
					// 
					if (is_numeric($opts[0])) {
						$items_set[] = array(
							'id_commande' => $id_commande,
							'objet' => $emplette['objet'],
							'id_objet' => $emplette['id_objet'],
							'descriptif' => generer_info_entite($emplette['id_objet'], $emplette['objet'], 'titre', '*').' offert@' . $opts[0],
							'quantite' => 1,
							'reduction' => $emplette['reduction'],
							'prix_unitaire_ht' => $prix_ht,
							'taxe' => $taxe,
							'statut' => 'attente'
						);
					} else {
						$items_set[] = array(
							'id_commande' => $id_commande,
							'objet' => $emplette['objet'],
							'id_objet' => $emplette['id_objet'],
							'descriptif' => generer_info_entite($emplette['id_objet'], $emplette['objet'], 'titre', '*'),
							'quantite' => 1,
							'reduction' => $emplette['reduction'],
							'prix_unitaire_ht' => $prix_ht,
							'taxe' => $taxe,
							'statut' => 'attente',
							'numero_debut' => $opts[1]
						);
						
						// 
						// Si un cadeau est présent pour cet abonnement, 
						// on ajoute une ligne à la commande.
						// 
						if (is_numeric($opts[2]) && $opts[2] > 0) {
							$obj = 'produit';
							$id_obj = $opts[2];
							
							$items_set[] = array(
								'id_commande' => $id_commande,
								'objet' => $obj,
								'id_objet' => $id_obj,
								'descriptif' => generer_info_entite($id_obj, $obj, 'titre', '*') . '  abonnements_offre#' . $emplette['id_objet'],
								'quantite' => 1,
								'reduction' => 0,
								'prix_unitaire_ht' => 0, // c'est un cadeau
								'taxe' => $taxe,
								'statut' => 'attente'
							);
						}
					}
				}
			} else {
				$items_set[] = array(
					'id_commande' => $id_commande,
					'objet' => $emplette['objet'],
					'id_objet' => $emplette['id_objet'],
					'descriptif' => generer_info_entite($emplette['id_objet'], $emplette['objet'], 'titre', '*'),
					'quantite' => $emplette['quantite'],
					'reduction' => $emplette['reduction'],
					'prix_unitaire_ht' => $prix_ht,
					'taxe' => $taxe,
					'statut' => 'attente'
				);
			}
			
			foreach ($items_set as $set) {
				$where = array();
				foreach ($set as $k => $w) {
					if (in_array($k, array('id_commande', 'objet', 'id_objet', 'descriptif', 'numero_debut'))) {
						$where[] = "$k=" . sql_quote($w);
					}
				}
				
				// 
				// Vérifier si on ajoute dans tous les cas 
				// ou si la ligne est absente
				// 
				if ($append OR ! $id_commandes_detail = sql_getfetsel("id_commandes_detail", "spip_commandes_details", $where)) {
					// Insérer
					$id_commandes_detail = objet_inserer('commandes_detail');
				}
				
				// 
				// Soit on modifie la ligne déjà existante,
				// ou on ajoute les données de la ligne créée.
				// 
				if ($id_commandes_detail) {
					//
					// C'est un abonnement avec un premier numéro, le cadeau
					// est peut-être présent. Il sera nécessaire au tour d'après
					// de connaître l'identifiant de la ligne relative
					// à l'abonnement.
					// 
					if ($set['objet'] == 'abonnements_offre' && $set['numero_debut']) {
						static $id_commandes_detail_abo_offre;
						$id_commandes_detail_abo_offre = $id_commandes_detail;
					}
					// 
					// Si c'est un cadeau, compléter le descriptif 
					// avec l'identifiant de la ligne de commande de l'abonnement
					// qui a été enregistré au tour précédent.
					// 
					if ($set['objet'] == 'produit' && $set['prix_unitaire_ht'] == 0) {
						$set['descriptif'] .= '-' . $id_commandes_detail_abo_offre;
						unset($id_commandes_detail_abo_offre);
					}
					
					objet_modifier('commandes_detail', $id_commandes_detail, $set);
					$details[] = $id_commandes_detail;
				}
			}
		}
		
		// 
		// Supprimer les details qui n'ont rien a voir avec ce panier
		// 
		if (!$append) {
			sql_delete("spip_commandes_details", "id_commande=" . intval($id_commande) . " AND " . sql_in('id_commandes_detail', $details, "NOT"));
		}

		// 
		// Envoyer aux plugins après édition pour verification eventuelle du contenu de la commande
		// 
		pipeline(
			'post_edition',
			array(
				'args' => array(
					'table' => 'spip_commandes',
					'id_objet' => $id_commande,
					'action' => 'vpaniers_options_vers_commande',
				),
				'data' => array()
			)
		);

	}
}
