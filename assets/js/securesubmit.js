(function (window, document, Heartland, wc_securesubmit_params) {
  var addHandler = Heartland.Events.addHandler;
  function toAll(elements, fun) {
    var i = 0;
    var length = elements.length;
    for (i; i < length; i++) {
      fun(elements[i]);
    }
  }

  function filter(elements, fun) {
    var i = 0;
    var length = elements.length;
    var result = [];
    for (i; i < length; i++) {
      if (fun(elements[i]) === true) {
        result.push(elements[i]);
      }
    }
    return result;
  }

  function clearFields() {
    toAll(document.querySelectorAll('.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message, .securesubmit_token'), function (element) {
      element.remove();
    });
  }

  function secureSubmitFormHandler(e) {
    console.log('form submit');

    var securesubmitMethod = document.getElementById('payment_method_securesubmit');
    var storedCards = document.querySelectorAll('input[name=secure_submit_card]');
    var storedCardsChecked = filter(storedCards, function (el) { return el.checked; });
    var tokens = document.querySelectorAll('input.securesubmit_token');

    if (securesubmitMethod && securesubmitMethod.checked
        && (storedCardsChecked.length === 0 || storedCardsChecked[0] && storedCardsChecked[0].value === 'new')
        && tokens.length === 0) {
      var card       = document.getElementById('securesubmit_card_number');
      var cvv        = document.getElementById('securesubmit_card_cvv');
      var expiration = document.getElementById('securesubmit_card_expiration');

      if (!expiration && expiration.value) {
        return false;
      }

      var split = expiration.value.split(' / ');
      var month = split[0].replace(/^\s+|\s+$/g, '');
      var year  = split[1].replace(/^\s+|\s+$/g, '');

      console.log('tokenizing');
      (new Heartland.HPS({
        publicKey: wc_securesubmit_params.key,
        cardNumber: card.value.replace(/\D/g, ''),
        cardCvv: cvv.value.replace(/\D/g, ''),
        cardExpMonth: month.replace(/\D/g, ''),
        cardExpYear: year.replace(/\D/g, ''),
        success: secureSubmitResponseHandler,
        error: secureSubmitResponseHandler
      })).tokenize();

      return false;
    }

    console.log('token present. submitting');

    return true;
  }

  function secureSubmitResponseHandler(response) {
    console.log('handling response');
    var form = document.querySelector('form.checkout, form#order_review');

    if (response.error) {
      console.log('tokenization error');
      var ul = document.createElement('ul');
      var li = document.createElement('li');
      clearFields();

      ul.classList.add('woocommerce_error');
      ul.classList.add('woocommerce-error');
      li.appendChild(document.createTextNode(response.error.message));
      ul.appendChild(li);

      document.querySelector('.securesubmit_new_card').insertBefore(
        ul,
        document.querySelector('.securesubmit_new_card_info')
      );
    } else {
      console.log('tokenization success');
      var token = document.createElement('input');
      var last4 = document.createElement('input');
      var cType = document.createElement('input');
      var expMo = document.createElement('input');
      var expYr = document.createElement('input');

      token.type = 'hidden';
      token.id = 'securesubmit_token';
      token.classList.add('securesubmit_token');
      token.name = 'securesubmit_token';
      token.value = response.token_value;

      last4.type = 'hidden';
      last4.name = 'last_four';
      last4.value = response.last_four;

      cType.type = 'hidden';
      cType.name = 'card_type';
      cType.value = response.card_type;

      expMo.type = 'hidden';
      expMo.name = 'exp_month';
      expMo.value = response.exp_month;

      expYr.type = 'hidden';
      expYr.name = 'exp_year';
      expYr.value = response.exp_year;

      form.appendChild(token);
      form.appendChild(last4);
      form.appendChild(cType);
      form.appendChild(expMo);
      form.appendChild(expYr);

      console.log('resubmitting');
      Heartland.Events.trigger('submit', form);

      toAll(document.querySelectorAll('.securesubmit_token'), function (el) { el.remove(); });
    }
  }

  window.securesubmitLoadEvents = function () {
    if (!Heartland) { return; }
    if (!document.querySelector('.securesubmit_new_card')) { return; }

    addHandler(document.querySelector('.securesubmit_new_card .card-number'), 'keydown', Heartland.Card.restrictNumberic);
    addHandler(document.querySelector('.securesubmit_new_card .card-number'), 'input', Heartland.Card.formatNumber);
    addHandler(document.querySelector('.securesubmit_new_card .card-number'), 'input', Heartland.Card.validateNumber);
    addHandler(document.querySelector('.securesubmit_new_card .card-number'), 'input', Heartland.Card.addType);

    addHandler(document.querySelector('.securesubmit_new_card .expiry-date'), 'keydown', Heartland.Card.restrictNumberic);
    addHandler(document.querySelector('.securesubmit_new_card .expiry-date'), 'input', Heartland.Card.formatExpiration);
    addHandler(document.querySelector('.securesubmit_new_card .expiry-date'), 'input', Heartland.Card.validateExpiration);

    addHandler(document.querySelector('.securesubmit_new_card .card-cvc'), 'keydown', Heartland.Card.restrictNumberic);
    addHandler(document.querySelector('.securesubmit_new_card .card-cvc'), 'input', Heartland.Card.validateCvv);
  };
  window.securesubmitLoadEvents();

  addHandler(document, 'DOMContentLoaded', function () {
    toAll(document.querySelectorAll('form.checkout'), function (element) {
      // WC 'checkout_place_order_securesubmit' event and jquery.triggerHandler
      // (http://api.jquery.com/triggerhandler/) workaround
      element.oncheckout_place_order_securesubmit = secureSubmitFormHandler;
    });

    toAll(document.querySelectorAll('form#order_review'), function (element) {
      addHandler(element, 'submit', secureSubmitFormHandler);
    });

    toAll(document.querySelectorAll('form.checkout, form#order_review'), function (element) {
      addHandler(element, 'change', function () {
        // jQuery('div.securesubmit_new_card').slideDown(200);
      });
    });

    toAll(document.querySelectorAll('.card-number, .card-cvc, .expiry-date'), function (element) {
      // addHandler(element, 'change', clearFields);
    });
  });
}(window, document, window.Heartland, window.wc_securesubmit_params));
