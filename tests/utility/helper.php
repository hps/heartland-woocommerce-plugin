<?php
/**
 * Test helper functions
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
 * Test helper functions
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */
class WC_Gateway_SecureSubmit_Tests_Utility_Helper
{
    /**
     * Captures output echo'd or printed
     *
     * @param callable $callable function to call
     * @param array    $args     arguments to the function
     *
     * @return string
     */
    public static function captureOutput($callable, $args = array())
    {
        ob_start();
        call_user_func_array($callable, $args);
        return ob_get_clean();
    }
}
