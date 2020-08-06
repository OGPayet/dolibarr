
$(function(){
    if (typeof changeTiers != 'undefined' && typeof urlConf != 'undefined') {
        if (window.location.pathname == urlConf && changeTiers == true) {
            $( "<a href='#' id='changetiersbtn'>"+pictoChangeTiers+"</a>" ).insertAfter($('.refidno a[href*="comm/card.php?socid="], .refidno a[href*="societe/card.php?socid="], .refidno a[href*="societe/soc.php?socid="]').last());

	    $(document).on('click', '#changetiersbtn', function() {
		$(formTiers).insertAfter(this);
		$('.refidno a[href*="comm/card.php?socid="], .refidno a[href*="societe/card.php?socid="], .refidno a[href*="societe/soc.php?socid="]').hide();
		$(this).hide();
		eval(scriptTiers);

		return false;
	    });

	    $(document).on('click', '#changetierscancelbtn', function() {
		$(this).parents('form').remove();
		$('.refidno a[href*="comm/card.php?socid="], .refidno a[href*="societe/card.php?socid="], .refidno a[href*="societe/soc.php?socid="]').show();
		$('#changetiersbtn').show();

		return false;
	    });
	}
    }
});
