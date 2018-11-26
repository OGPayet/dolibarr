jQuery(document).ready(function () {
$('a', '.tabsAction').each(function(){
	// Button minimiser
	var action=$( this ).attr('href').match(/action=([a-z]+)/gi)
	$( this ).attr('title', $(this).text());

	if (	action == 'action=reopen'
		||	action == 'action=edit'
		|| 	action == 'action=modif'
		|| 	action == 'action=modify'
		) {
		$(this).html('<span class=\'ui-icon ui-icon-pencil\'></span>');
		$(this).css({background:'#80FFFF'});
	};
	if (action == 'action=presend' ) {
		$(this).html('<span class=\'ui-icon ui-icon-mail-closed\'></span>');
		$(this).css({background:'#BCBD8C'});
	};
	if (action == 'action=clone' ) {
		$(this).html('<span class=\'ui-icon ui-icon-copy\'></span>');
		$(this).css({background:'#DDDDDD'});
	};

	if (action == 'action=reconcile' ) {
		$(this).html('<span class=\'ui-icon ui-icon-flag\'></span>');
		$(this).css({background:'#FFB164'});
	};

	if ($( this ).attr('href').match(/shipment.php/)=='shipment.php') {
		$(this).html('<span class=\'ui-icon ui-icon-tag\'></span>');
		$(this).css({background:'#FFFF80'});
	};

	// pour les désactiver
	if ($( this ).attr('href')=='#') {
		$(this).html('<span class=\'ui-icon ui-icon-circle-close\'></span>');
		$(this).css({background:'#FFF'});
	};


	if (action == 'action=create' ) {
		if ($( this ).attr('href').match(/paiement.php/)=='paiement.php') {
			$(this).html('<span class=\'ui-icon ui-icon-star\'></span>');
			$(this).css({background:'#FFFF80'});
		} else if ($( this ).attr('href').match(/comm\/propal/)=='comm/propal') {
			$(this).html('<span class=\'ui-icon ui-icon-calculator\'></span>');
			$(this).css({background:'#80FF80'});
		} else if ($( this ).attr('href').match(/fourn\/facture/)=='fourn/facture') {
			$(this).html('<span class=\'ui-icon ui-icon-suitcase\'></span>');
			$(this).css({background:'#76BABA'});
		} else if ($( this ).attr('href').match(/compta\/facture/)=='compta/facture') {
			$(this).html('<span class=\'ui-icon ui-icon-suitcase\'></span>');
			$(this).css({background:'#76BABA'});
		} else if ($( this ).attr('href').match(/commande\/card.php/)=='commande/card.php') {
			$(this).html('<span class=\'ui-icon ui-icon-heart\'></span>');
			$(this).css({background:'#53FF7E'});
		} else if ($( this ).attr('href').match(/fichinter\/card.php/)=='fichinter/card.php') {
			$(this).html('<span class=\'ui-icon ui-icon-wrench\'></span>');
			$(this).css({background:'#FF48FF'});

		} else if ($( this ).attr('href').match(/contrat\/card.php/)=='contrat/card.php') {
			$(this).html('<span class=\'ui-icon ui-icon-script\'></span>');
			$(this).css({background:'#F5CA89'});
		} else if ($( this ).attr('href').match(/contact\/card.php/)=='contact/card.php') {
			$(this).html('<span class=\'ui-icon ui-icon-person\'></span>');
			$(this).css({background:'#F5CA89'});

		} else {
			$(this).html('<span class=\'ui-icon ui-icon-document\'></span>');
			$(this).css({background:'#DDDDDD'});
		};
	};
	if (	action == 'action=close'
		||	action == 'action=cancel'

		) {
		$(this).html('<span class=\'ui-icon ui-icon-closethick\'></span>');
		$(this).css({background:'#80FF80'});
	};

	if ( action == 'action=classifyunbilled' ) {
		$(this).html('<span class=\'ui-icon ui-icon-arrowreturnthick-1-w\'></span>');
		$(this).css({background:'#BF80FF'});
	};


	if (	action == 'action=statut'
		||	action == 'action=classifyclosed'
		||	action == 'action=classifybilled'
		||	action == 'action=classifydone'
		||	action == 'action=sendToValidate'
		||	action == 'action=valid'
		||	action == 'action=paid'
		||	action == 'action=validate'

	) {
		$(this).html('<span class=\'ui-icon ui-icon-check\'></span>');
		$(this).css({background:'#80FF80'});
	};
	if (action == 'action=merge' ) {
		$(this).html('<span class=\'ui-icon ui-icon-clipboard\'></span>');
		$(this).css({background:'#DDDDDD'});
	};
	if (action == 'action=delete' ) {
		$(this).html('<span class=\'ui-icon ui-icon-trash\'></span>');
		$(this).css({background:'#FF7373'});
	};
	if (action == 'action=shipped' ) {
		$(this).html('<span class=\'ui-icon ui-icon-cart\'></span>');
		$(this).css({background:'#80FF80'});
	};


	if (	action == 'action=canceled'
		|| 	action == 'action=disable'
		||	action == 'action=refuse'
	) {
		$(this).html('<span class=\'ui-icon ui-icon-cancel\'></span>');
		$(this).css({background:'#DDDDDD'});
	};
});

$('#action-delete', '.tabsAction').each(function(){
	$( this ).attr('title', $(this).text());
	$(this).html('<span class=\'ui-icon ui-icon-trash\'></span>');
	$(this).css({background:'#FF7373'});

});

})