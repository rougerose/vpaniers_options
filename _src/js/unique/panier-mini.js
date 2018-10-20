// =====================================
// Panier-mini
// =====================================

var PanierMini = (function(){
	'use strict';
	var self = {},
		$panier = undefined,
		$ouvrirPanier = undefined,
		$fermerPanier = undefined,
		$overlay = undefined,
		$body = undefined,
		panierBlocAjax = 'panierMini',
		iconePanierBlocAjax = 'iconePanier';

	function update() {
		ajaxReload(iconePanierBlocAjax);
		ajaxReload(panierBlocAjax, { callback: toggle});
	}

	function toggle() {
		$panier.toggleClass('est-ouvert');
		$overlay.toggleClass('est-visible');
		$body.toggleClass('u-noscroll');
	}

	self.init = function() {
		$body = $('body');
		$panier = $('#panierMini');
		$ouvrirPanier = $('.js-icone-panier');
		$fermerPanier = $('.js-fermer-panier');
		$overlay = $('.js-panier-mini-overlay');
		self.bindActions();
	};

	self.updatePanier = function() {
		// console.log('updatePanier');
		update($panier);
	};

	self.togglePanier = function() {
		// console.log('toggle', $panier);
		toggle();
	};
	
	self.bindActions = function() {
		// console.log('bind');
		$ouvrirPanier.on('click', function(event) {
			event.preventDefault();
			self.togglePanier();
		});
		$fermerPanier.on('click', function(event) {
			event.preventDefault();
			self.togglePanier();
		});
	};
	
	self.ajouterNumero = function() {
		var timer;
		window.clearTimeout(timer);
		timer = window.setTimeout(self.updatePanier, 1000);
	};

	return self;
})();

$(function() {
	PanierMini.init();
	// PanierMini.updatePanier();
});
