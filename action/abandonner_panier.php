<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

/**
 * abandonner un panier
 * Alias de action_supprimer_panier du plugin Paniers
 * Fonction appelée par action_abandonner_transaction, or elle n'existe pas.
 * 
 * @param  int $id_panier
 * 
 */
function action_abandonner_panier_dist($id_panier) {
	$supprimer_panier = charger_fonction('supprimer_panier', 'action');
	$supprimer_panier($id_panier);
}
