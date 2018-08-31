<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

/**
 * Remplir une commande d'après un panier
 *
 * Dérivée de la fonction panier2commande_remplir_commande du plugin Paniers
 * pour permettre les traitements spécifiques aux abonnements. En effet, chaque
 * abonnement embarque un champ Options qui décrit ses caractéristiques
 * (abonnement personnel ou offert) et s'il contient également un cadeau.
 * Ces options sont donc traitées à part et détaillées en autant d'articles 
 * "individuels" pour la commande. 
 * 
 * @param  int  $id_auteur
 * @param  int  $id_commande
 * @param  int  $id_panier
 * @param  boolean $ajouter
 * 		Vérifier le contenu du panier pour ajouter des éléments qui n'y seraient
 * 		pas déjà.
 * @return void 
 */
function vpaniers_options_remplir_commande($id_auteur, $id_commande, $id_panier, $ajouter = true) {
	include_spip('action/editer_objet');
	include_spip('inc/filtres');
	include_spip('inc/config');
	include_spip('action/editer_abonnement');
	
	// Noter le panier dans le champ Source de la commande
	objet_modifier('commande', $id_commande, array('source' => "panier#$id_panier"));
	
	// Recopier le contenu du panier dans la commande
	$panier = sql_allfetsel(
		'*', 'spip_paniers_liens', 'id_panier = ' . intval($id_panier)
	);
	
	// Pour chaque élément du panier, on va remplir la commande
	// (ou verifier que la ligne est deja dans la commande)
	if ($panier and is_array($panier)) {
		
		$fonction_prix = charger_fonction('prix', 'inc/');
		$fonction_prix_ht = charger_fonction('ht', 'inc/prix');
		
		$tva_abonnements = lire_config('vabonnements/taxe');
		
		$details = array();
		$details_set = array();
		
		foreach ($panier as $item) {
			$prix_ht = $fonction_prix_ht($item['objet'], $item['id_objet'], 6);
			$prix = $fonction_prix($item['objet'], $item['id_objet'], 6);
			
			// Les abonnements du panier
			if ($item['objet'] == 'abonnements_offre') {
				
				$options = vpaniers_options_expliquer_options($item['options']);
				
				foreach ($options as $cle => $champs) {
					// Prix souscripteur ?
					if (strlen($champs['prix_souscripteur'])) {
						$prix_unitaire_ht = ($champs['prix_souscripteur'] / (1 + $tva_abonnements)) * 1;
					} else {
						$prix_unitaire_ht = $prix_ht;
					}
					
					$descriptif = _T('vpaniers:abonnement_resume_label_abonnement')
						.' '
						.generer_info_entite($item['id_objet'], $item['objet'], 'titre')
						.' / '
						.filtre_numeros_nombre_en_clair(generer_info_entite($item['id_objet'], $item['objet'], 'duree', '*')
					);
					
					// Abonnement offert ? Compléter le descriptif
					if (strlen($champs['nom_inscription'])) {
						$descriptif .= " offert@ "
							.$champs['nom_inscription']
							." "
							.$champs['prenom'];
					}
					
					$c = array(0 => $champs);
					$options_detail = vpaniers_options_produire_options($c);
					
					$details_set[] = array(
						'id_commande' => $id_commande,
						'objet' => $item['objet'],
						'id_objet' => $item['id_objet'],
						'quantite' => 1,
						'statut' => 'attente',
						'taxe' => $tva_abonnements,
						'descriptif' => $descriptif,
						'prix_unitaire_ht' => $prix_unitaire_ht,
						'options' => $options_detail,
					);
					
					// Un cadeau est lié à l'abonnement ?
					if (strlen($champs['cadeau']) and $id_cadeau = intval($champs['cadeau']) > 0) {
						$details_set[] = array(
							'id_commande' => $id_commande,
							'objet' => 'produit',
							'id_objet' => $id_cadeau,
							'quantite' => 1,
							'statut' => 'attente',
							'descriptif' => generer_info_entite($id_cadeau, 'produit', 'titre').' cadeau@'.$item['objet'].'-'.$item['id_objet'],
							'prix_unitaire_ht' => 0 // c'est un cadeau
						);
					}
				}
			} else {
				// les autres objets du panier qui ne nécessitent pas 
				// un traitement particulier
				
				if ($prix_ht > 0) {
					$taxe = round(($prix - $prix_ht) / $prix_ht, 6);
				} else {
					$taxe = 0;
				}
				
				$details_set[] = array(
					'id_commande' => $id_commande,
					'objet' => $item['objet'],
					'id_objet' => $item['id_objet'],
					'descriptif' => generer_info_entite($item['id_objet'], $item['objet'], 'titre'),
					'quantite' => $item['quantite'],
					'reduction' => $item['reduction'],
					'prix_unitaire_ht' => $prix_ht,
					'taxe' => $taxe,
					'statut' => 'attente'
				);
			}
		}
		
		// 
		// On lance un nouvelle boucle sur $details_set car le nombre total 
		// d'éléments à ajouter peut être supérieur au nombre total d'éléments 
		// du panier, compte tenu de la manière dont les abonnements 
		// et les cadeaux sont enregistrés.
		// 
		foreach ($details_set as $i => $set) {
			$where = array();
			
			foreach ($set as $k => $val) {
				if (in_array($k, array('id_commande', 'objet', 'id_objet', 'options'))) {
					$where[] = "$k=".sql_quote($val);
				}
			}
			// Si l'élément n'est pas déjà dans la commande, on créé une nouvelle ligne
			if ($ajouter or !$id_commandes_detail = sql_getfetsel('id_commandes_detail', 'spip_commandes_details', $where)) {
				$id_commandes_detail = objet_inserer('commandes_detail');
			}
			
			if ($id_commandes_detail) {
				objet_modifier('commandes_detail', $id_commandes_detail, $set);
				$details[] = $id_commandes_detail;
			}
		}
		
		if (!$ajouter) {
			// supprimer les details qui n'ont rien a voir avec ce panier
			sql_delete("spip_commandes_details", "id_commande=" . intval($id_commande) . " AND " . sql_in('id_commandes_detail', $details, "NOT"));
		}
		
		// Envoyer aux plugins après édition pour vérification éventuelle 
		// du contenu de la commande
		pipeline(
			'post_edition',
			array(
				'args' => array(
					'table' => 'spip_commandes',
					'id_objet' => $id_commande,
					'action' => 'remplir_commande',
				),
				'data' => array()
			)
		);
	}
}
