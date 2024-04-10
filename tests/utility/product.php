<?php

/**
 * Test products
 *
 * PHP version 5.2+
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Test products
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */
class WC_Gateway_SecureSubmit_Tests_Utility_Product
{
    /**
     * Create simple product.
     *
     * @return WC_Product_Simple
     */
    public static function createSimpleProduct()
    {
        // Create the product
        $product = wp_insert_post(
            array(
                'post_title'  => 'Dummy Product',
                'post_type'   => 'product',
                'post_status' => 'publish'
            )
        );

        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $order = wc_get_order($product);
            $order->update_meta_data('_price', '10');
            $order->update_meta_data('_regular_price', '10');
            $order->update_meta_data('_sale_price', '');
            $order->update_meta_data('_sku', 'DUMMY SKU');
            $order->update_meta_data('_manage_stock', 'no');
            $order->update_meta_data('_tax_status', 'taxable');
            $order->update_meta_data('_downloadable', 'no');
            $order->update_meta_data('_virtual', 'taxable');
            $order->update_meta_data('_visibility', 'visible');
            $order->update_meta_data('_stock_status', 'instock');
        } else {
            update_post_meta($product, '_price', '10');
            update_post_meta($product, '_regular_price', '10');
            update_post_meta($product, '_sale_price', '');
            update_post_meta($product, '_sku', 'DUMMY SKU');
            update_post_meta($product, '_manage_stock', 'no');
            update_post_meta($product, '_tax_status', 'taxable');
            update_post_meta($product, '_downloadable', 'no');
            update_post_meta($product, '_virtual', 'taxable');
            update_post_meta($product, '_visibility', 'visible');
            update_post_meta($product, '_stock_status', 'instock');
        }
        
        return new WC_Product_Simple($product);
    }
}
