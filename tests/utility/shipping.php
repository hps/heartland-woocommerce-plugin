<?php
/**
 * Test shipping
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
 * Test shipping
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */
class WC_Gateway_SecureSubmit_Tests_Utility_Shipping
{
    /**
     * Create a simple flat rate at the cost of 10.
     *
     * @return void
     */
    public static function createSimpleFlatRate()
    {
        $settings = array(
            'enabled'      => 'yes',
            'title'        => 'Flat Rate',
            'availability' => 'all',
            'countries'    => '',
            'tax_status'   => 'taxable',
            'cost'         => '10'
        );
        update_option('woocommerce_flat_rate_settings', $settings);
        update_option('woocommerce_flat_rate', array());
    }
}
