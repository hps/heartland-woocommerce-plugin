jQuery( function() {
	jQuery('form.checkout').on('checkout_place_order_securesubmit', function( event ) {
		return secureSubmitFormHandler();
	});
	
	jQuery('form#order_review').submit(function(){
		return secureSubmitFormHandler();
	});

	jQuery("form.checkout, form#order_review").on('change', '.card-number, .card-cvc, .card-expiry-month, .card-expiry-year', function( event ) {
		jQuery('.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message, .securesubmit_token').remove();
		jQuery('.securesubmit_token').remove();
	});

	jQuery("form.checkout, form#order_review").on('change', function() {
		jQuery('div.securesubmit_new_card').slideDown( 200 );
	} );
} );

function secureSubmitFormHandler() {
	if ( jQuery('#payment_method_securesubmit').is(':checked') ) {
		if ( jQuery( 'input.securesubmit_token' ).size() == 0 ) {

			var card 	= jQuery('.card-number').val().replace(/\D/g, '');
			var cvc 	= jQuery('.card-cvc').val();
			var month	= jQuery('.card-expiry-month').val();
			var year	= jQuery('.card-expiry-year').val();
			var $form = jQuery("form.checkout, form#order_review");

			$form.block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6}});

			hps.tokenize({
				data: {
					public_key: wc_securesubmit_params.key,
					number: card,
					cvc: cvc,
					exp_month: month,
					exp_year: year
				},
				success: function(response) {
					secureSubmitResponseHandler(response);
				},
				error: function(response) {
					secureSubmitResponseHandler(response);
				}
			});
			
			return false;
		}
	}

	return true;
}

function secureSubmitResponseHandler( response ) {

    var $form = jQuery("form.checkout, form#order_review");

    if ( response.message ) {
        jQuery('.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message, .securesubmit_token').remove();
        jQuery('.card-number').closest('p').before( '<ul class="woocommerce_error woocommerce-error"><li>' + response.message + '</li></ul>' );
        $form.unblock();
    } else {
        $form.append("<input type='hidden' class='securesubmit_token' name='securesubmit_token' value='" + response.token_value + "'/>");
        $form.submit();
    }
}