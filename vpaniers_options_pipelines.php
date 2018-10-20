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
	
	$formulaires = array('souscrire_abonnement', 'offrir_abonnement');

	if (in_array($flux['args']['form'], $formulaires) and $flux['args']['je_suis_poste']) {
		// pour le formulaire offrir_abonnement vérification que l'on est bien à la fin de l'étape 2
		if ($flux['args']['form'] == 'offrir_abonnement' and ($flux['args']['contexte']['_etape'] <= $flux['args']['contexte']['_etapes'] and count($flux['args']['contexte']['erreurs']))) {
			return $flux;
		} else {
			$script = http_script("PanierMini.updatePanier();");
			$flux['data'] .= $script;
		}
	}
	
	return $flux;
}
