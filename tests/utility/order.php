<?php
/**
 * Test orders
 *
 * PHP version 5.2+
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */

/**
 * Test orders
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */
class WC_Gateway_SecureSubmit_Tests_Utility_Order
{
    /**
     * Creates an order
     *
     * @param string $pmSlug slug of a payment method
     *
     * @return WC_Order
     */
    public static function create($pmSlug)
    {
        // Create product
        $product = WC_Gateway_SecureSubmit_Tests_Utility_Product::createSimpleProduct();
        WC_Gateway_SecureSubmit_Tests_Utility_Shipping::createSimpleFlatRate();

        $order_data = array(
            'status'        => 'pending',
            'customer_id'   => 1,
            'customer_note' => '',
            'total'         => '',
        );

        // Required, else wc_create_order throws an exception
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $order                  = wc_create_order($order_data);

        // Add order products
        $order->add_product($product, 4);

        // Set billing address
        $billing_address = array(
            'country'    => 'US',
            'first_name' => 'Jeroen',
            'last_name'  => 'Sormani',
            'company'    => 'WooCompany',
            'address_1'  => 'WooAddress',
            'address_2'  => '',
            'postcode'   => '123456',
            'city'       => 'WooCity',
            'state'      => 'NY',
            'email'      => 'admin@example.org',
            'phone'      => '555-32123',
        );
        $order->set_address($billing_address, 'billing');

        // Add shipping costs
        $shipping_taxes = WC_Tax::calc_shipping_tax(
            '10',
            WC_Tax::get_shipping_tax_rates()
        );
        $order->add_shipping(
            new WC_Shipping_Rate(
                'flat_rate_shipping',
                'Flat rate shipping',
                '10',
                $shipping_taxes,
                'flat_rate'
            )
        );

        // Set payment gateway
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        $order->set_payment_method($payment_gateways[$pmSlug]);

        // Set totals
        $order->set_total(10, 'shipping');
        $order->set_total(0, 'cart_discount');
        $order->set_total(0, 'cart_discount_tax');
        $order->set_total(0, 'tax');
        $order->set_total(0, 'shipping_tax');
        $order->set_total(40, 'total'); // 4 x $10 simple helper product

        return wc_get_order($order->id);
    }
}
