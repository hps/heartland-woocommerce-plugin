(function(window, document, Heartland, wc_securesubmit_params) {
    var addHandler = Heartland.Events.addHandler;

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
        toAll(document.querySelectorAll('.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message, .securesubmit_token'), function(element) {
            element.remove();
        });
    }

    // Handles form submission when not using iframes
    function formHandler(e) {
        var securesubmitMethod = document.getElementById('payment_method_securesubmit');
        var storedCards = document.querySelectorAll('input[name=secure_submit_card]');
        var storedCardsChecked = filter(storedCards, function(el) {
            return el.checked;
        });
        var tokens = document.querySelectorAll('input.securesubmit_token');

        if (securesubmitMethod && securesubmitMethod.checked && (storedCardsChecked.length === 0 || storedCardsChecked[0] && storedCardsChecked[0].value === 'new') && tokens.length === 0) {
            var card = document.getElementById('securesubmit_card_number');
            var cvv = document.getElementById('securesubmit_card_cvv');
            var expiration = document.getElementById('securesubmit_card_expiration');

            if (!expiration && expiration.value) {
                return false;
            }

            var split = expiration.value.split(' / ');
            var month = split[0].replace(/^\s+|\s+$/g, '');
            var year = split[1].replace(/^\s+|\s+$/g, '');

            (new Heartland.HPS({
                publicKey: wc_securesubmit_params.key,
                cardNumber: card.value.replace(/\D/g, ''),
                cardCvv: cvv.value.replace(/\D/g, ''),
                cardExpMonth: month.replace(/\D/g, ''),
                cardExpYear: year.replace(/\D/g, ''),
                success: responseHandler,
                error: responseHandler
            })).tokenize();

            return false;
        }

        return true;
    }

    // Handles form submission when using iframes
    function iframeFormHandler(e) {
        var securesubmitMethod = document.getElementById('payment_method_securesubmit');
        var storedCards = document.querySelectorAll('input[name=secure_submit_card]');
        var storedCardsChecked = filter(storedCards, function(el) {
            return el.checked;
        });
        var tokens = document.querySelectorAll('input.securesubmit_token');

        if (securesubmitMethod && securesubmitMethod.checked && (storedCardsChecked.length === 0 || storedCardsChecked[0] && storedCardsChecked[0].value === 'new') && tokens.length === 0) {
            wc_securesubmit_params.hps.Messages.post({
                    accumulateData: true,
                    action: 'tokenize',
                    message: wc_securesubmit_params.key
                },
                'cardNumber'
            );
            return false;
        }

        return true;
    }

    // Handles tokenization response
    function responseHandler(response) {
        var form = document.querySelector('form.checkout, form#order_review');

        if (response.error) {
            var ul = document.createElement('ul');
            var li = document.createElement('li');
            clearFields();

            addClass(ul, 'woocommerce_error');
            addClass(ul, 'woocommerce-error');
            li.appendChild(document.createTextNode(response.error.message));
            ul.appendChild(li);

            document.querySelector('.securesubmit_new_card').insertBefore(
                ul,
                document.querySelector('.securesubmit_new_card_info')
            );
        } else {
            var token = document.createElement('input');
            var last4 = document.createElement('input');
            var cType = document.createElement('input');
            var expMo = document.createElement('input');
            var expYr = document.createElement('input');

            token.type = 'hidden';
            token.id = 'securesubmit_token';
            addClass(token, 'securesubmit_token');
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

            Heartland.Events.trigger('submit', form);

            toAll(document.querySelectorAll('.securesubmit_token'), function(el) {
                el.remove();
            });
        }
    }

    // Load function to attach event handlers when WC refreshes payment fields
    window.securesubmitLoadEvents = function() {
        if (!Heartland) {
            return;
        }

        toAll(document.querySelectorAll('.card-number, .card-cvc, .expiry-date'), function(element) {
            addHandler(element, 'change', clearFields);
        });

        toAll(document.querySelectorAll('.saved-selector'), function(element) {
            addHandler(element, 'click', function(e) {
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

        if (document.querySelector('.securesubmit_new_card .card-number')) {
            Heartland.Card.attachNumberEvents('.securesubmit_new_card .card-number');
            Heartland.Card.attachExpirationEvents('.securesubmit_new_card .expiry-date');
            Heartland.Card.attachCvvEvents('.securesubmit_new_card .card-cvc');
        }
    };
    window.securesubmitLoadEvents();

    // Load function to build iframes when WC refreshes payment fields
    window.securesubmitLoadIframes = function() {
        if (!wc_securesubmit_params.use_iframes) {
            return;
        }
        wc_securesubmit_params.hps = new Heartland.HPS({
            publicKey: wc_securesubmit_params.key,
            type: 'iframe',
            fields: {
                cardNumber: {
                    target: 'securesubmit_card_number',
                    placeholder: '•••• •••• •••• ••••'
                },
                cardExpiration: {
                    target: 'securesubmit_card_expiration',
                    placeholder: 'MM / YYYY'
                },
                cardCvv: {
                    target: 'securesubmit_card_cvv',
                    placeholder: 'CVV'
                }
            },
            style: {
                'input': {
                    'background': '#fff',
                    'border': '1px solid',
                    'border-color': '#bbb3b9 #c7c1c6 #c7c1c6',
                    'box-sizing': 'border-box',
                    'font-family': 'Verdana',
                    'font-size': '18px',
                    'line-height': '1',
                    'margin': '0 .5em 0 0',
                    'max-width': '100%',
                    'outline': '0',
                    'padding': '0.5278em',
                    'vertical-align': 'baseline',
                    'width': '100%'
                },
                '#heartland-field-body': {
                    'width': '100%'
                },
                '#heartland-field[name="cardNumber"]': {
                  'background-image': 'url("' + wc_securesubmit_params.images_dir + '/ss-inputcard-blank@2x.png")',
                  'background-position': 'right',
                  'background-repeat': 'no-repeat',
                  'background-size': '77px 40px'
                },
                '#heartland-field.card-type-visa': {
                  'background-image': 'url("' + wc_securesubmit_params.images_dir + '/ss-inputcard-visa@2x.png")'
                },
                '#heartland-field.card-type-jcb': {
                  'background-image': 'url("' + wc_securesubmit_params.images_dir + '/ss-inputcard-jcb@2x.png")'
                },
                '#heartland-field.card-type-discover': {
                  'background-image': 'url("' + wc_securesubmit_params.images_dir + '/ss-inputcard-discover@2x.png")'
                },
                '#heartland-field.card-type-amex': {
                  'background-image': 'url("' + wc_securesubmit_params.images_dir + '/ss-inputcard-amex@2x.png")'
                },
                '#heartland-field.card-type-mastercard': {
                  'background-image': 'url("' + wc_securesubmit_params.images_dir + '/ss-inputcard-mastercard@2x.png")'
                }
            },
            onTokenSuccess: responseHandler,
            onTokenError: responseHandler
        });
    };

    addHandler(document, 'DOMContentLoaded', function() {
        var handler = formHandler;
        if (wc_securesubmit_params.use_iframes) {
            handler = iframeFormHandler;
        }

        toAll(document.querySelectorAll('form.checkout'), function(element) {
            // WC 'checkout_place_order_securesubmit' event and jquery.triggerHandler
            // (http://api.jquery.com/triggerhandler/) workaround
            element.oncheckout_place_order_securesubmit = handler;
        });

        toAll(document.querySelectorAll('form#order_review'), function(element) {
            addHandler(element, 'submit', handler);
        });

        toAll(document.querySelectorAll('form.checkout, form#order_review'), function(element) {
            addHandler(element, 'change', function() {
                // jQuery('div.securesubmit_new_card').slideDown(200);
            });
        });
    });
}(window, document, window.Heartland, window.wc_securesubmit_params));
