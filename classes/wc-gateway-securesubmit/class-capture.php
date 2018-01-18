<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_Gateway_SecureSubmit_Capture
{
    protected $parent = null;

    public function __construct(&$parent = null)
    {
        $this->parent = $parent;
    }

    public function call($orderId)
    {
        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        $payment_action = get_post_meta($orderId, '_payment_action', true);

        $transactionId = $this->parent->getOrderTransactionId($order);

        if (!$transactionId) {
            return false;
        }

        try {
            $chargeService = $this->parent->getCreditService();
            try {
                if ($payment_action == 'verify') {
                    $verify_card = get_post_meta($orderId, '_verify_secure_submit_card', true);
                    $verify_amount = get_post_meta($orderId, '_verify_Amount', true);
                    $verify_currency = get_post_meta($orderId, '_verify_Currency', true);
                    $verify_details = get_post_meta($orderId, '_verify_Details', true);
                    $verify_descriptor = get_post_meta($orderId, '_verify_Descriptor', true);
                    $verify_cardholder = get_post_meta($orderId, '_verify_Cardholder', true);

                    $response = $chargeService->charge()
                        ->withAmount(wc_format_decimal($order->get_total() - $order->get_total_refunded(), 2))
                        ->withCurrency($verify_currency)
                        ->withToken($verify_card->token_value) // does this need to be a hpstoken object?
                        ->withCardHolder($verify_cardholder)
                        ->withDetails($details)
                        ->withAllowDuplicates(true)
                        ->withTxnDescriptor($verify_descriptor)
                        ->execute();
                } else {
                    $response = $chargeService->capture()
                        ->withTransactionId($transactionId)
                        ->withAmount(wc_format_decimal($order->get_total() - $order->get_total_refunded(), 2))
                        ->execute();
                }

                $order->add_order_note(__('SecureSubmit payment captured', 'wc_securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
                return true;
            } catch (HpsException $e) {
                $this->parent->throwUserError($e->getMessage());
            }
        } catch (Exception $e) {
            $error = __('Error:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            $this->parent->displayUserError($error);
            return false;
        }
    }

    /**
     * Adds delayed capture functionality to the "Edit Order" screen
     *
     * @param array $actions
     *
     * @return array
     */
    public function addOrderAction($actions)
    {
        $actions[$this->parent->id . '_capture'] = __('Capture credit card authorization', 'wc_securesubmit');
        return $actions;
    }
}
