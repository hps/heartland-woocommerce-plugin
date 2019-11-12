<?php

if (!defined('ABSPATH')) {
    exit();
}

use GlobalPayments\Api\Entities\EncryptionData;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\Api\PaymentMethods\CreditTrackData;
use GlobalPayments\Api\Services\CreditService;
use GlobalPayments\Api\ServicesConfig;
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\Entities\Transaction;

class WC_Gateway_SecureSubmit_Refund
{
    protected $parent = null;

    public function __construct(&$parent = null)
    {
        $this->parent = $parent;
        $serviceCreditResponse = $this->parent->getCreditService();
    }

    public function call($orderId, $amount, $reason)
    {
        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        $transactionId = $this->parent->getOrderTransactionId($order);
        $hpsaddress = $this->parent->getOrderAddress($order);
        $cardHolder = $this->parent->getOrderCardHolder($order, $hpsaddress);
        
        if (!$transactionId) {
            return false;
        }

        try {
            $this->parent->getCreditService();
            $chargeService = Transaction::fromId($transactionId);
            try {
                $response = $chargeService->refund()
                    ->withAmount(wc_format_decimal($amount, 2))
                    ->withCurrency(strtolower(get_woocommerce_currency()))
                    ->withTransactionId($transactionId)
                    ->withCardHolder($cardHolder)
                    ->execute();
                $order->add_order_note(__('SecureSubmit payment refunded', 'wc_securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
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
}
