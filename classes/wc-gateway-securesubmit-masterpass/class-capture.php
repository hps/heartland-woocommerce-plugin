<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

if (!defined('ABSPATH')) {
    exit();
}

class WC_Gateway_SecureSubmit_MasterPass_Capture
{
    protected $masterpass = null;

    public function __construct(&$masterpass = null)
    {
        $this->masterpass = $masterpass;
    }

    public function call($order)
    {
        try {
            if (!$order) {
                throw new Exception(__('Order cannot be found', 'wc_securesubmit'));
            }

            $orderId = WC_SecureSubmit_Util::getData($order, 'get_id', 'id');

            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                $masterpassOrderId = wc_get_order($orderId)->get_meta('_masterpass_order_id');
            } else {
                $masterpassOrderId = get_post_meta($orderId, '_masterpass_order_id', true);
            }

            if (!$masterpassOrderId) {
                throw new Exception(__('MasterPass order id cannot be found', 'wc_securesubmit'));
            }

            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                $masterpassPaymentStatus = wc_get_order($orderId)->get_meta('_masterpass_payment_status');
            } else {
                $masterpassPaymentStatus = get_post_meta($orderId, '_masterpass_payment_status', true);
            }

            if ($masterpassPaymentStatus !== 'authorized') {
                throw new Exception(sprintf(
                    /* translators: %s: masterpassPaymentStatus */
                    esc_html__('Transaction has already been %s','wc_securesubmit' ),
                    $masterpassPaymentStatus)
                );
            }

            $service = $this->masterpass->getService();

            $orderData = new HpsOrderData();
            $orderData->currencyCode = strtolower(get_woocommerce_currency());

            $response = $service->capture(
                $masterpassOrderId,
                $order->get_total(),
                $orderData
            );

            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                wc_get_order($orderId)->update_meta_data('_masterpass_payment_status', 'captured');
            } else {
                update_post_meta($orderId, '_masterpass_payment_status', 'captured', 'authorized');
            }

            $order->add_order_note(__('MasterPass payment captured', 'wc_securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
            return true;
        } catch (Exception $e) {
            $error = __('Error:', 'wc_securesubmit') . ' "' . (string)$e->getMessage() . '"';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else if (method_exists(WC(), 'add_error')) {
                WC()->add_error($error);
            }
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
        $actions[$this->masterpass->id . '_capture'] = __('Capture MasterPass authorization', 'wc_securesubmit');
        return $actions;
    }
}
