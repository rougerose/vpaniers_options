<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

/**
 * Afficher le mini panier après un achat
 *
 * Au rechargement des formulaires d'abonnement,
 * ajouter l'appel à la fonction de JS de rechargement 
 * du mini panier et de son icone, puis afficher le contenu du mini panier.
 * 
 * @param  array $flux
 * @return array
 */
function vpaniers_options_formulaire_fond($flux) {

	if ($flux['args']['form'] == 'souscrire_abonnement' and $flux['args']['je_suis_poste']) {
		$script = http_script("PanierMini.updatePanier();");
		$flux['data'] .= $script;
	}
	
	return $flux;
}
