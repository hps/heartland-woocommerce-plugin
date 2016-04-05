<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_Gateway_SecureSubmit_Payment
{
    protected $parent = null;

    public function __construct(&$parent = null)
    {
        $this->parent = $parent;
    }

    public function call($orderId)
    {
        $order = wc_get_order($orderId);
        $securesubmit_token = isset($_POST['securesubmit_token']) ? woocommerce_clean($_POST['securesubmit_token']) : '';

        // used for card saving:
        $last_four = isset($_POST['last_four']) ? woocommerce_clean($_POST['last_four']) : '';
        $exp_month = isset($_POST['exp_month']) ? woocommerce_clean($_POST['exp_month']) : '';
        $exp_year = isset($_POST['exp_year']) ? woocommerce_clean($_POST['exp_year']) : '';
        $card_type = isset($_POST['card_type']) ? woocommerce_clean($_POST['card_type']) : '';

        if (isset($_POST['save_card']) && $_POST['save_card'] === "true") {
            $save_card_to_customer = true;
        } else {
            $save_card_to_customer = false;
        }

        try {
            $post_data = array();

            if (empty($securesubmit_token)) {
                if (isset($_POST['secure_submit_card']) && $_POST['secure_submit_card'] === 'new') {
                    throw new Exception(__('Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'wc_securesubmit'));
                }
            }

            $chargeService = $this->parent->getCreditService();
            $hpsaddress = $this->parent->getOrderAddress($order);
            $cardHolder = $this->parent->getOrderCardHolder($order, $hpsaddress);

            $hpstoken = new HpsTokenData();

            if (
                is_user_logged_in() && isset($_POST['secure_submit_card']) &&
                $_POST['secure_submit_card'] !== 'new'
            ) {
                $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

                if (isset($cards[$_POST['secure_submit_card']]['token_value'])) {
                    $hpstoken->tokenValue = $cards[$_POST['secure_submit_card']]['token_value'];
                    $save_card_to_customer = false;
                } else {
                    throw new Exception(__('Invalid saved card.', 'wc_securesubmit'));
                }
            } else {
                $hpstoken->tokenValue = $securesubmit_token;
            }

            $details = new HpsTransactionDetails();
            $details->invoiceNumber = $order->id;

            try {
                if ($this->parent->paymentaction == 'sale') {
                    $response = $chargeService->charge(
                        $order->order_total,
                        strtolower(get_woocommerce_currency()),
                        $hpstoken,
                        $cardHolder,
                        $save_card_to_customer, // multi-use
                        $details
                    );
                } else {
                    $response = $chargeService->authorize(
                        $order->order_total,
                        strtolower(get_woocommerce_currency()),
                        $hpstoken,
                        $cardHolder,
                        $save_card_to_customer, // multi-use
                        $details
                    );
                }

                if ($save_card_to_customer) {
                    if (is_user_logged_in()) {
                        $tokenval = $response->tokenData->tokenValue;

                        if ($response->tokenData->responseCode == '0') {
                            switch (strtolower($card_type)) {
                            case 'mastercard':
                                $card_type = 'MasterCard';
                                break;
                            default:
                                $card_type = ucfirst($card_type);
                                break;
                            }
                            add_user_meta(get_current_user_id(), '_secure_submit_card', array(
                                'last_four' => $last_four,
                                'exp_month' => $exp_month,
                                'exp_year' => $exp_year,
                                'token_value' => (string) $tokenval,
                                'card_type' => $card_type,
                            ));
                        }
                    }
                }

                $verb = $this->parent->paymentaction == 'sale'
                      ? 'captured'
                      : 'authorized';
                $order->add_order_note(__('SecureSubmit payment ' . $verb, 'wc_securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
                $order->payment_complete($response->transactionId);
                WC()->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $this->parent->get_return_url($order)
                );
            } catch (HpsException $e) {
                if ($e->getCode()== HpsExceptionCodes::POSSIBLE_FRAUD_DETECTED && $this->parent->email_fraud == 'yes' && $this->parent->fraud_address != '') {
                    wc_mail(
                        $this->parent->fraud_address,
                        'Suspicious order ' . ($this->parent->allow_fraud == 'yes' ? 'allowed' : 'declined') . ' (' . $order_id . ')',
                        'Hello,<br><br>Heartland has determined that you should review order ' . $order_id . ' for the amount of ' . $order->order_total . '.<p><br></p>'.
                        '<p>You have received this email because you have configured the \'Email store owner on suspicious orders\' settings in the [WooCommerce | Checkout | SecureSubmit] options page.</p>'
                    );
                }

                if ($this->parent->allow_fraud == 'yes' && $e->getCode() == HpsExceptionCodes::POSSIBLE_FRAUD_DETECTED ) {
                    // we can skip the card saving: if it fails for possible fraud there will be no token.
                    $order->update_status('on-hold', __('<strong>Accepted suspicious transaction.</strong> Please use Virtual Terminal to review.', 'wc_securesubmit'));
                    $order->reduce_order_stock();
                    $cart->empty_cart();

                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );
                } else {
                    if ($e->getCode() == HpsExceptionCodes::POSSIBLE_FRAUD_DETECTED) {
                        $this->parent->displayUserError($this->parent->fraud_text);
                    } else {
                        $this->parent->displayUserError($e->getMessage());
                    }

                    return array(
                        'result'   => 'fail',
                        'redirect' => ''
                    );
                }
            }
        } catch (Exception $e) {
            $error = __('Error:', 'wc_securesubmit') . ' "' . (string)$e->getMessage() . '"';
            $this->parent->displayUserError($error);

            return array(
                'result'   => 'fail',
                'redirect' => ''
            );
        }
    }
}
