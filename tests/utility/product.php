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

        return new WC_Product_Simple($product);
    }
}
