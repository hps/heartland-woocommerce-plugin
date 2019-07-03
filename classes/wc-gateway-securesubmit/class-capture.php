<?php
use GlobalPayments\Api\Entities\EncryptionData;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\Api\PaymentMethods\CreditTrackData;
use GlobalPayments\Api\Services\CreditService;
use GlobalPayments\Api\ServicesConfig;
use GlobalPayments\Api\ServicesContainer;
use PHPUnit\Framework\TestCase;

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

        $orderId = WC_SecureSubmit_Util::getData($order, 'get_id', 'id');
        error_log('order id: ' . $orderId);
        $payment_action = get_post_meta($orderId, '_heartland_order_payment_action', true);

        error_log('payment action: ' . print_r($payment_action, true));

        $transactionId = $this->parent->getOrderTransactionId($order);

        error_log(print_r($transactionId, true));

        if (!$transactionId) {
            return false;
        }

        try {
            $chargeService = $this->parent->getCreditService();
            try {
                if ($payment_action == 'verify') {
                    $verify_card = get_post_meta($orderId, '_verify_secure_submit_card', true);
                    $verify_amount = get_post_meta($orderId, '_verify_amount', true);
                    $verify_currency = get_post_meta($orderId, '_verify_currency', true);
                    $verify_details = get_post_meta($orderId, '_verify_details', true);
                    $verify_descriptor = get_post_meta($orderId, '_verify_descriptor', true);
                    $verify_cardholder = get_post_meta($orderId, '_verify_cardholder', true);

                    error_log(print_r($verify_card, true));
                    error_log(print_r($verify_amount, true));
                    error_log(print_r($verify_currency, true));
                    error_log(print_r($verify_details, true));
                    error_log(print_r($verify_descriptor, true));
                    error_log(print_r($verify_cardholder, true));

                    $response = $chargeService->charge()
                        ->withAmount(wc_format_decimal($order->get_total() - $order->get_total_refunded(), 2))
                        ->withCurrency($verify_currency)
                        ->withToken($verify_card['token_value'])
                        ->withCardHolder($verify_cardholder)
                        ->withDetails($details)
                        ->withAllowDuplicates(true)
                        ->withTxnDescriptor($verify_descriptor)
                        ->execute();

                    $order->payment_complete($response->transactionId);
                } else {
                    $response = $chargeService->capture()
                        ->withTransactionId($transactionId)
                        ->withAmount(wc_format_decimal($order->get_total() - $order->get_total_refunded(), 2))
                        ->execute();
                }

                $order->add_order_note(__('SecureSubmit payment captured', 'wc_securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
                return true;
            } catch (HpsException $e) {
                error_log($e->getMessage());
                $this->parent->throwUserError($e->getMessage());
            }
        } catch (Exception $e) {
            $error = __('Error:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            $this->parent->displayUserError($error);
            error_log($error);
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
