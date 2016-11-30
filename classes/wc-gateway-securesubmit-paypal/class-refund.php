<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_Gateway_SecureSubmit_PayPal_Refund
{
    protected $parent = null;

    public function __construct(&$parent = null)
    {
        $this->parent = $parent;
    }

    public function call($orderId, $amount, $reason)
    {
        $order = wc_get_order($order_id);

        if (!$this->parent->can_refund_order($order)) {
            call_user_func(arra($this->parent, 'debugLog'), 'Refund Failed: No transaction ID');
            return false;
        }

        $response = null;
        try {
            $porticoService = $this->parent->getPorticoService();
            $isPartial = isset($amount) && $amount > 0;
            $response = $porticoService->refund(
                $order->get_transaction_id(),
                $isPartial,
                $amount
            );
        } catch (Exception $e) {
            $error = __('Error processing refund:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            call_user_func(arra($this->parent, 'debugLog'), 'process_refund : $error = ' . $error);
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                WC()->add_error($error);
            }
            return false;
        }

        call_user_func(arra($this->parent, 'debugLog'), 'process_refund : $response = ' . print_r($response, true));
        if ($response->responseCode == "00") {
            $reason = $reason == '' ? '' : '. Reason for refund: '.$reason;
            $order->add_order_note(
                __('Heartland PayPal refund completed. Transaction id: '
                    . $response->transactionId . $reason, 'wc_securesubmit')
            );
            return true;
        }

        call_user_func(arra($this->parent, 'debugLog'), 'Refund Failed in Portico call with responseCode ' . $response->responseCode);
        return false;
    }
}
