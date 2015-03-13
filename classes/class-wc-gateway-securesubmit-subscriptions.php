<?php

class WC_Gateway_SecureSubmit_Subscriptions extends WC_Gateway_SecureSubmit
{
    public function __construct()
    {
        parent::__construct();

        $this->supports = array(
                            'products',
                            'refunds',
                            'subscriptions',
                            'subscription_cancellation',
                            'subscription_reactivation',
                            'subscription_suspension',
                            'subscription_amount_changes',
                            'subscription_payment_method_change',
                            'subscription_date_changes',
                          );

        add_action('scheduled_subscription_payment_' . $this->id, array($this, 'scheduledSubscriptionPayment'), 10, 3);
        add_action('woocommerce_subscriptions_changed_failing_payment_method_' . $this->id, array($this, 'updateFailingPaymentMethod'), 10, 2);
    }

    public function process_payment($orderId)
    {
        global $woocommerce;

        $order = new WC_Order($orderId);
        $securesubmitToken = isset($_POST['securesubmit_token']) ? woocommerce_clean($_POST['securesubmit_token']) : '';

        // used for card saving:
        $last_four = isset($_POST['last_four']) ? woocommerce_clean($_POST['last_four']) : '';
        $exp_month = isset($_POST['exp_month']) ? woocommerce_clean($_POST['exp_month']) : '';
        $exp_year = isset($_POST['exp_year']) ? woocommerce_clean($_POST['exp_year']) : '';
        $card_type = isset($_POST['card_type']) ? woocommerce_clean($_POST['card_type']) : '';
        $saveCard = isset($_POST['save_card']) ? woocommerce_clean($_POST['save_card']) : '';

        try {
            $post_data = array();

            if (empty($securesubmitToken)) {
                if (isset($_POST['secure_submit_card']) && $_POST['secure_submit_card'] === 'new') {
                    throw new Exception(__('Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'wc_securesubmit'));
                }
            }

            $config = new HpsServicesConfig();
            $config->secretApiKey = $this->secret_key;
            $config->versionNumber = '1510';
            $config->developerId = '002914';

            $chargeService = new HpsCreditService($config);

            $hpsaddress = new HpsAddress();
            $hpsaddress->address = $order->billing_address_1;
            $hpsaddress->city = $order->billing_city;
            $hpsaddress->state = $order->billing_state;
            $hpsaddress->zip = preg_replace('/[^a-zA-Z0-9]/', '', $order->billing_postcode);
            $hpsaddress->country = $order->billing_country;

            $cardHolder = new HpsCardHolder();
            $cardHolder->firstName = $order->billing_first_name;
            $cardHolder->lastName = $order->billing_last_name;
            $cardHolder->phone = preg_replace('/[^0-9]/', '', $order->billing_phone);
            $cardHolder->emailAddress = $order->billing_email;
            $cardHolder->address = $hpsaddress;

            $hpstoken = new HpsTokenData();

            if (is_user_logged_in() && isset($_POST['secure_submit_card']) && $_POST['secure_submit_card'] !== 'new') {
                $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

                if (isset($cards[$_POST['secure_submit_card']]['token_value'])) {
                    $hpstoken->tokenValue = $cards[$_POST['secure_submit_card']]['token_value'];
                } else {
                    throw new Exception(__('Invalid saved card.', 'wc_securesubmit'));
                }
            } else {
                $hpstoken->tokenValue = $securesubmitToken;
            }

            $details = new HpsTransactionDetails();
            $details->invoiceNumber = $order->id;

            try {
                if ($saveCard === "true") {
                    $saveCardToCustomer = true;
                } else {
                    $saveCardToCustomer = false;
                }

                if ($this->paymentaction == 'sale') {
                    $response = $chargeService->charge(
                        $order->order_total,
                        strtolower(get_woocommerce_currency()),
                        $hpstoken,
                        $cardHolder,
                        $saveCardToCustomer, // multi-use
                        $details
                    );
                } else {
                    $response = $chargeService->authorize(
                        $order->order_total,
                        strtolower(get_woocommerce_currency()),
                        $hpstoken,
                        $cardHolder,
                        $saveCardToCustomer, // multi-use
                        $details
                    );
                }

                if ($saveCardToCustomer) {
                    if (is_user_logged_in()) {
                        $tokenval = $response->tokenData->tokenValue;

                        if ($response->tokenData->responseCode == '0') {
                            add_user_meta(get_current_user_id(), '_secure_submit_card', array(
                                'last_four' => $last_four,
                                'exp_month' => $exp_month,
                                'exp_year' => $exp_year,
                                'token_value' => (string) $tokenval,
                                'card_type' => $card_type,
                            ));
                            add_post_meta($order->id, '_securesubmit_card_token', (string)$tokenval, true);
                        }
                    }
                }

                $order->add_order_note(__('SecureSubmit payment completed', 'hps-securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
                $order->payment_complete();
                $woocommerce->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } catch (HpsException $e) {
                throw new Exception(__($e->getMessage(), 'wc_securesubmit'));
            }
        } catch (Exception $e) {
            $error = __('Error:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            return;
        }
    }

    public function processSubscriptionPayment($orderId, $amount)
    {
        global $woocommerce;

        $order = new WC_Order($orderId);
        $token = get_post_meta($new->id, '_securesubmit_card_token', true);

        if (!isset($token)) {
            return new WP_Error();
        }

        if (!isset($this->paymentaction)) {
            $this->init_settings();
            $this->paymentaction = $this->settings['paymentaction'];
        }

        try {
            $details = new HpsTransactionDetails();
            $details->invoiceNumber = $order->id;

            $hpsaddress = new HpsAddress();
            $hpsaddress->address = $order->billing_address_1;
            $hpsaddress->city = $order->billing_city;
            $hpsaddress->state = $order->billing_state;
            $hpsaddress->zip = preg_replace('/[^a-zA-Z0-9]/', '', $order->billing_postcode);
            $hpsaddress->country = $order->billing_country;

            $cardHolder = new HpsCardHolder();
            $cardHolder->firstName = $order->billing_first_name;
            $cardHolder->lastName = $order->billing_last_name;
            $cardHolder->phone = preg_replace('/[^0-9]/', '', $order->billing_phone);
            $cardHolder->emailAddress = $order->billing_email;
            $cardHolder->address = $hpsaddress;

            if ($this->paymentaction == 'sale') {
                $response = $chargeService->charge(
                    $amount,
                    strtolower(get_woocommerce_currency()),
                    $token,
                    $cardHolder,
                    false,
                    $details
                );
            } else {
                $response = $chargeService->authorize(
                    $amount,
                    strtolower(get_woocommerce_currency()),
                    $token,
                    $cardHolder,
                    false,
                    $details
                );
            }

            return true;
        } catch (Exception $e) {
            return new WP_Error();
        }
    }

    public function scheduledSubscriptionPayment($amount, $order, $productId)
    {
        $result = $this->processSubscriptionPayment($order, $amount);

        if (is_wp_error($result)) {
            WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order, $productId);
        } else {
            WC_Subscriptions_Manager::process_subscription_payments_on_order($order);
        }
    }

    public function updateFailingPaymentMethod($old, $new) {
        update_post_meta($old->id, '_securesubmit_card_token', get_post_meta($new->id, '_securesubmit_card_token', true));
    }
}
new WC_Gateway_SecureSubmit_Subscriptions();
