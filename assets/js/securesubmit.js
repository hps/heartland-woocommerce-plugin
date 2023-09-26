(function (window, document, GlobalPayments, wc_securesubmit_params) {
  var addHandler = window.GlobalPayments
    ? GlobalPayments.events.addHandler
    : function () { };

  function addClass(element, klass) {
    if (element.className.indexOf(klass) === -1) {
      element.className = element.className + ' ' + klass;
    }
  }

  function removeClass(element, klass) {
    if (element.className.indexOf(klass) === -1) return;
    element.className = element.className.replace(klass, '');
  }

  function toAll(elements, fun) {
    var i = 0;
    var length = elements.length;
    for (i; i < length; i++) {
      fun(elements[i]);
    }
  }

  function clearFields() {
    toAll(
      document.querySelectorAll(
        '.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message'
      ),
      function (element) {
        element.remove();
      }
    );
  }

  window.__secureSubmitFrameInit = window.__secureSubmitFrameInit || false;

  // Handles tokenization response
  function responseHandler(response) {
    var form = document.querySelector('form.checkout, form#order_review');

    if (response.error || (response.heartland && response.heartland.error)) {
      var ul = document.createElement('ul');
      var li = document.createElement('li');
      clearFields();

      addClass(ul, 'woocommerce_error');
      addClass(ul, 'woocommerce-error');
      
      var error_message = response?.error?.message ?? response?.reasons[0]?.message;
      
      li.appendChild(
        document.createTextNode(
          error_message.replace('undefined', 'missing')
        )
      );
      ul.appendChild(li);

      document
        .querySelector('.securesubmit_new_card')
        .insertBefore(
          ul,
          document.querySelector('.securesubmit_new_card_info')
        );
    } else {
      if (document.getElementById("last_four"))
        document.getElementById("last_four").remove();

      if (document.getElementById("card_type"))
        document.getElementById("card_type").remove();

      if (document.getElementById("exp_month"))
        document.getElementById("exp_month").remove();

      if (document.getElementById("exp_year"))
        document.getElementById("exp_year").remove();

      if (document.getElementById("bin"))
        document.getElementById("bin").remove();

      var token = document.getElementById('securesubmit_token');
      var last4 = document.createElement('input');
      var cType = document.createElement('input');
      var expMo = document.createElement('input');
      var expYr = document.createElement('input');
      var bin = document.createElement('input');

      token.value = response.paymentReference;

      response.details = response.details || {};

      last4.type = 'hidden';
      last4.id = 'last_four';
      last4.name = 'last_four';
      last4.value = response.details.cardLast4;

      cType.type = 'hidden';
      cType.id = 'card_type';
      cType.name = 'card_type';
      cType.value = response.details.cardType;

      expMo.type = 'hidden';
      expMo.id = 'exp_month';
      expMo.name = 'exp_month';
      expMo.value = response.details.expiryMonth;

      expYr.type = 'hidden';
      expYr.id = 'exp_year';
      expYr.name = 'exp_year';
      expYr.value = response.details.expiryYear;

      bin.type = 'hidden';
      bin.id = 'bin';
      bin.name = 'bin';
      bin.value = response.details.cardBin;

      form.appendChild(last4);
      form.appendChild(cType);
      form.appendChild(expMo);
      form.appendChild(expYr);
      form.appendChild(bin);

      jQuery(form).submit();
    }

    setTimeout(function () {
      document.getElementById('securesubmit_token').value = '';
    }, 500);
  }

  // Load function to attach event handlers when WC refreshes payment fields
  window.securesubmitLoadEvents = function () {
    if (!GlobalPayments) {
      return;
    }

    toAll(
      document.querySelectorAll('.card-number, .card-cvc, .expiry-date'),
      function (element) {
        addHandler(element, 'change', clearFields);
      }
    );

    toAll(document.querySelectorAll('.saved-selector'), function (element) {
      addHandler(element, 'click', function (e) {
        var display = 'none';
        if (document.getElementById('secure_submit_card_new').checked) {
          display = 'block';
        }
        toAll(document.querySelectorAll('.new-card-content'), function (el) {
          el.style.display = display;
        });

        // Set active flag
        toAll(document.querySelectorAll('.saved-card'), function (el) {
          removeClass(el, 'active');
        });
        addClass(element.parentNode.parentNode, 'active');
      });
    });
  };

  window.securesubmitLoadEvents();

  // Load function to build iframes when WC refreshes payment fields
  window.securesubmitLoadIframes = function () {
    if (!wc_securesubmit_params) {
      return;
    }

    let buttonTarget = document.createElement("div");
    buttonTarget.id = "submit_button";
    buttonTarget.style = "display:none";

    let wooOrderButton = document.getElementById("place_order");

    wooOrderButton.parentElement.insertBefore(buttonTarget, wooOrderButton);

    wooOrderButton.addEventListener("click", handleSubmitButton, true);    

    GlobalPayments.configure({
      publicApiKey: wc_securesubmit_params.key
    });

    wc_securesubmit_params.hps = GlobalPayments.ui.form({
      fields: {
        "card-number": {
          placeholder: "•••• •••• •••• ••••",
          target: "#securesubmit_card_number"
        },
        "card-expiration": {
          placeholder: "MM / YYYY",
          target: "#securesubmit_card_expiration"
        },
        "card-cvv": {
          placeholder: "•••",
          target: "#securesubmit_card_cvv"
        },
        "submit": {
          target: "#submit_button",
          text: "PLACE ORDER"
        }
      },
      styles: {
        'html' : {
          "-webkit-text-size-adjust": "100%"
        },
        'body' : {
          'width' : '100%'
        },
        '#secure-payment-field-wrapper' : {
          'position' : 'relative',
          'width' : '100%'
        },
        '#secure-payment-field' : {
          'background-color' : '#fff',
          'border'           : '1px solid #ccc',
          'border-radius'    : '4px',
          'display'          : 'block',
          'font-size'        : '14px',
          'height'           : '35px',
          'padding'          : '6px 12px',
          'width'            : '100%',
        },
        '#secure-payment-field:focus' : {
          "border": "1px solid lightblue",
          "box-shadow": "0 1px 3px 0 #cecece",
          "outline": "none"
        },
        'button#secure-payment-field.submit' : {
          "border": "0",
          "border-radius": "0",
          "background": "none",
          "background-color": "#333333",
          "border-color": "#333333",
          "color": "#fff",
          "cursor": "pointer",
          "padding": ".6180469716em 1.41575em",
          "text-decoration": "none",
          "font-weight": "600",
          "text-shadow": "none",
          "display": "inline-block",
          "-webkit-appearance": "none",
          "height": "initial",
          "width": "100%",
          "flex": "auto",
          "position": "static",
          "margin": "0",
          "white-space": "pre-wrap",
          "margin-bottom": "0",
          "float": "none",
          "font": "600 1.41575em/1.618 Source Sans Pro,HelveticaNeue-Light,Helvetica Neue Light,\r\n                Helvetica Neue,Helvetica,Arial,Lucida Grande,sans-serif !important"
        },
        '#secure-payment-field[type=button]' : {
          "width": "100%"
        },
        '#secure-payment-field[type=button]:focus' : {
          "color": "#fff",
          "background": "#000000",
          "width": "100%"
        },
        '#secure-payment-field[type=button]:hover' : {
          "color": "#fff",
          "background": "#000000"
        },
        '.card-cvv' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/cvv.png) no-repeat right',
          'background-size' : '63px 40px'
        },
        '.card-cvv.card-type-amex' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/cvv-amex.png) no-repeat right',
          'background-size' : '63px 40px'
        },
        '.card-number' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-unknown@2x.png) no-repeat right',
          'background-size' : '55px 35px'
        },
        '.card-number.invalid.card-type-amex' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-amex@2x.png) no-repeat right',
          'background-position-y' : '-41px',
          'background-size' : '50px 90px'
        },
        '.card-number.invalid.card-type-discover' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-discover@2x.png) no-repeat right',
          'background-position-y' : '-44px',
          'background-size' : '85px 90px'
        },
        '.card-number.invalid.card-type-jcb' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-jcb@2x.png) no-repeat right',
          'background-position-y' : '-44px',
          'background-size' : '55px 94px'
        },
        '.card-number.invalid.card-type-mastercard' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-mastercard@2x.png) no-repeat right',
          'background-position-y' : '-41px',
          'background-size' : '82px 86px'
        },
        '.card-number.invalid.card-type-visa' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-visa@2x.png) no-repeat right',
          'background-position-y' : '-44px',
          'background-size' : '83px 88px',
        },
        '.card-number.valid.card-type-amex' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-amex@2x.png) no-repeat right',
          'background-position-y' : '3px',
          'background-size' : '50px 90px',
        },
        '.card-number.valid.card-type-discover' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-discover@2x.png) no-repeat right',
          'background-position-y' : '1px',
          'background-size' : '85px 90px'
        },
        '.card-number.valid.card-type-jcb' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-jcb@2x.png) no-repeat right top',
          'background-position-y' : '2px',
          'background-size' : '55px 94px'
        },
        '.card-number.valid.card-type-mastercard' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-mastercard@2x.png) no-repeat right',
          'background-position-y' : '2px',
          'background-size' : '82px 86px'
        },
        '.card-number.valid.card-type-visa' : {
          'background' : 'transparent url(' + wc_securesubmit_params.images_dir + '/logo-visa@2x.png) no-repeat right top',
          'background-size' : '82px 86px'
        },
        '.card-number::-ms-clear' : {
          'display' : 'none'
        },
        'input[placeholder]' : {
          'letter-spacing' : '.5px',
        },
      }
    });

    wc_securesubmit_params.hps.ready(
      function () {
        if (
          document.getElementById("securesubmit_card_number") 
          && document.getElementById("securesubmit_card_number").firstChild
        )
          document.getElementById("securesubmit_card_number").firstChild.style.minHeight = "50px";
    
        if (
          document.getElementById("securesubmit_card_expiration") 
          && document.getElementById("securesubmit_card_expiration").firstChild
        )
          document.getElementById("securesubmit_card_expiration").firstChild.style.minHeight = "50px";
    
        if (
          document.getElementById("securesubmit_card_cvv") 
          && document.getElementById("securesubmit_card_cvv").firstChild
        )
          document.getElementById("securesubmit_card_cvv").firstChild.style.minHeight = "50px";
      }
    );

    // use WooCommerce order button when Single-Use Token isn't needed
    wc_securesubmit_params.hps.on("submit", "click", function () {
      if (!document.getElementById("secure_submit_card_new")) return;
      if (!document.getElementById("secure_submit_card_new").checked)
        document.getElementById("place_order").click();
    });

    wc_securesubmit_params.hps.on("token-success", function(resp) {
      responseHandler(resp);
    });

    wc_securesubmit_params.hps.on("token-error", function(resp) {
      responseHandler(resp);
    });
  };
  window.securesubmitLoadIframes();

  function processGiftCardResponse(msg) {
    var giftCardResponse = JSON.parse(msg);

    if (giftCardResponse.error === 1) {
      jQuery('#gift-card-error')
        .text(giftCardResponse.message)
        .show('fast');
    } else if (giftCardResponse.error === 0) {
      jQuery('#gift-card-success')
        .text('Your gift card was applied to the order.')
        .show('fast');
      jQuery('body').trigger('update_checkout');
      jQuery('#gift-card-number').val('');
      jQuery('#gift-card-pin').val('');
    }
  }

  window.removeGiftCards = function (clickedElement) {
    var removedCardID = jQuery(clickedElement).attr('id');

    jQuery
      .ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'remove_gift_card',
          securesubmit_card_id: removedCardID,
        },
      })
      .done(function () {
        jQuery('body').trigger('update_checkout');
      });
  };

  window.applyGiftCard = function () {
    jQuery
      .ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'use_gift_card',
          gift_card_number: jQuery('#gift-card-number').val(),
          gift_card_pin: jQuery('#gift-card-pin').val(),
        },
      })
      .success(processGiftCardResponse);
  };

  window.reloadIframes = function () {
    if (
      document.getElementById("securesubmit_card_number") 
      && document.getElementById("securesubmit_card_number").firstChild
    )
      document.getElementById("securesubmit_card_number").firstChild.remove();

    if (
      document.getElementById("securesubmit_card_expiration") 
      && document.getElementById("securesubmit_card_expiration").firstChild
    )
      document.getElementById("securesubmit_card_expiration").firstChild.remove();

    if (
      document.getElementById("securesubmit_card_cvv") 
      && document.getElementById("securesubmit_card_cvv").firstChild
    )
      document.getElementById("securesubmit_card_cvv").firstChild.remove();

    if (
      document.getElementById("submit_button") 
      && document.getElementById("submit_button").firstChild
    )
      document.getElementById("submit_button").firstChild.remove();

    window.securesubmitLoadIframes();
  };

  var triggerSubmit = function () {
    // manually include iframe submit button
    const fields = ['submit'];
    const target = wc_securesubmit_params.hps.frames['card-number'];

    for (const type in wc_securesubmit_params.hps.frames) {
      if (wc_securesubmit_params.hps.frames.hasOwnProperty(type)) {
        fields.push(type);
      }
    }

    for (const type in wc_securesubmit_params.hps.frames) {
      if (!wc_securesubmit_params.hps.frames.hasOwnProperty(type)) {
        continue;
      }

      const frame = wc_securesubmit_params.hps.frames[type];

      if (!frame) {
        continue;
      }

      GlobalPayments.internal.postMessage.post({
        data: {
          fields: fields,
          target: target.id
        },
        id: frame.id,
        type: 'ui:iframe-field:request-data'
      }, frame.id);
    }
  }

  function handleSubmitButton (event) {
    if (
      document.getElementById("payment_method_securesubmit")
      && document.getElementById("payment_method_securesubmit").checked
    ) {
      if (document.getElementById("secure_submit_card_new")) {
        if (document.getElementById("secure_submit_card_new").checked) {
          event.preventDefault();
          triggerSubmit();
        }
      } else {
        event.preventDefault();
        triggerSubmit();
      }
    }
  }

  jQuery('body').on('update_checkout', function() {
    window.reloadIframes();
  });
})(window, document, window.GlobalPayments, window.wc_securesubmit_params);
