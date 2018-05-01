<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Remplir un panier avec un objet quelconque
 *
 * Surcharge action_remplir_panier_dit du plugin Paniers,
 * pour prendre en charge les options d'abonnement
 * 
 * @param string $arg
 */
function action_remplir_panier($arg=null) {
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	
	// 
	// On récupère les données de l'argument
	// 
	@list($objet, $id_objet, $quantite, $negatif, $options) = explode('-', $arg);
	
	// 
	// Données des options d'abonnement : 
	// coupon/numero/cadeau/action/cle
	// 
	// action = ajout ou modifier
	// cle = clé de l'index que l'on souhaite modifier. Pour l'ajout, inutile de préciser.
	// 
	if (isset($options)) {
		@list($coupon, $numero, $cadeau, $faire, $cle) = explode('/', $options);
		
		$options_ajout = array($coupon, $numero, $cadeau);
	}

	$paniers_arrondir_quantite = charger_fonction('paniers_arrondir_quantite', 'inc');
	
	if (!isset($quantite) or is_null($quantite) or !strlen($quantite)) {
		$quantite = 1;
	}

	$quantite = $paniers_arrondir_quantite($quantite, $objet, $id_objet);

	// 
	// Si la quantite est nulle, on ne fait rien
	// 
	if ($quantite <= 0) {
		return;
	}

	// 
	// Retirer un objet du panier
	// 
	if (isset($negatif) && $negatif) {
		$quantite = $paniers_arrondir_quantite(-1 * $quantite, $objet, $id_objet);
	}
		
	// 
	// Il faut chercher le panier du visiteur en cours
	// 
	include_spip('inc/paniers');
	$id_panier_base = 0;
	if ($id_panier = paniers_id_panier_encours()) {
		// 
		// Le panier est bien en base ?
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
	// S'il n'y a pas de panier, on le crée
	// 
	if (!$id_panier OR !$id_panier_base) {
		$id_panier = paniers_creer_panier();
	}

	// 
	// On ne fait que s'il y a bien un panier existant et un objet valable
	// 
	if ($id_panier > 0 and $objet and $id_objet) {
		// 
		// Il faut maintenant chercher si cet objet précis est *déjà* dans le panier
		// 
		$deja = sql_fetsel(
			'options, quantite',
			'spip_paniers_liens',
			'id_panier=' . intval($id_panier) . ' and objet=' . sql_quote($objet) . ' and id_objet=' . intval($id_objet)
		);
		
		$quantite_deja = $paniers_arrondir_quantite($deja['quantite'], $objet, $id_objet);
		
		// 
		// Si on a déjà une quantité, on fait une mise à jour.
		// Sinon on ajoute l'objet dans le panier.
		// 
		if ($quantite_deja > 0) {
			
			$cumul_quantite = $paniers_arrondir_quantite($quantite_deja + $quantite, $objet, $id_objet);
			
			// 
			// Si le cumul_quantite est 0, on efface.
			// Sinon on met à jour.
			// 
			if ($cumul_quantite <= 0) {
				sql_delete('spip_paniers_liens', 'id_panier = ' . intval($id_panier) . ' and objet = ' . sql_quote($objet) . ' and id_objet = ' . intval($id_objet));
				
			} else {
				// 
				// Traitement spécifique des données d'options d'abonnement.
				// 
				// S'il faut faire un ajout : 
				// le tableau des options et la quantité est modifiée.
				// On ajoute la clé dans l'url afin que les données issues 
				// des étapes suivantes (numéro de départ d'abonnement 
				// et choix du cadeau) soient bien ajoutées à cet item identifié
				// par sa clé.
				// 
				// S'il faut modifier un item du tableau :
				// La clé pour l'identifier est indiquée explicitement.
				// 
				if ($objet == 'abonnements_offre' && isset($faire) && $faire == 'ajout' && isset($options_ajout)) {
					$options_deja = unserialize($deja['options']);
					$options_deja[] = $options_ajout;
					$options = serialize($options_deja);
					
					sql_updateq(
						'spip_paniers_liens', 
						array('quantite' => $cumul_quantite, 'options' => $options), 
						'id_panier = ' . intval($id_panier) . ' and objet = ' . sql_quote($objet) . ' and id_objet = ' . intval($id_objet)
					);
					
					end($options_deja);
					$key = key($options_deja);
					
					if ($redirect = _request('redirect')) {
    					include_spip('inc/headers');
						$redirect = parametre_url(parametre_url($redirect, 'cle', ''), 'cle', $key, '&');
						redirige_par_entete($redirect);
					}
					
				} elseif ($objet == 'abonnements_offre' && isset($faire) && $faire == 'modifier' && isset($cle) && isset($options_ajout)) {
					$options_deja = unserialize($deja['options']);
					$options_deja[$cle] = $options_ajout;
					$options = serialize($options_deja);
					
					sql_updateq(
						'spip_paniers_liens',
						array('options' => $options),
						'id_panier = ' . intval($id_panier) . ' and objet = ' . sql_quote($objet) . ' and id_objet = ' . intval($id_objet));
				}
			}
		} else {
			$opt = array($options_ajout);
			$options = serialize($opt);
			
			$id_panier_lien = sql_insertq(
				'spip_paniers_liens',
				array(
					'id_panier' => $id_panier,
					'objet' => $objet,
					'id_objet' => $id_objet,
					'quantite' => $quantite,
					'options' => $options
				)
			);
		}
		
		// 
		// Mais dans tous les cas on met la date du panier à jour
		// 
		sql_updateq(
			'spip_paniers',
			array('date'=>date('Y-m-d H:i:s')),
			'id_panier = '.intval($id_panier)
		);
	}

	// 
	// Appel du pipeline remplir_panier pour ajouter des traitements, vérifications
	// 
	$args_pipeline = array(
		'id_panier' => $id_panier,
		'objet' => $objet,
		'id_objet' => $id_objet,
		'quantite' => $quantite,
		'negatif' => $negatif,
		'options' => $options
	);	
	
	if (isset($id_panier_lien)){
		$args_pipeline['id_panier_lien'] = $id_panier_lien;	
	}
	
	pipeline(
		'remplir_panier',
		array(
			'args' => $args_pipeline
		)
	);
	
	// 
	// On vide le cache de l'objet sur lequel on vient de travailler.
	// 
	include_spip('inc/invalideur');
	suivre_invalideur("id='$objet/$id_objet'");
}
