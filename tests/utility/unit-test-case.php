<?php
/**
 * Base test case
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
 * Base test case
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */
class WC_Gateway_SecureSubmit_Tests_Utility_UnitTestCase extends WP_UnitTestCase
{
    protected $instance = null;

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->instance = WC_Gateway_SecureSubmit::instance();
    }

    /**
     * Tear down the test
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }
}
