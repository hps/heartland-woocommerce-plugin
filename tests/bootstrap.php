<?php
/**
 * PHPUnit bootstrap file
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
 * PHPUnit bootstrap file
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */
class WC_Gateway_SecureSubmit_Tests_Bootstrap
{
    public $wpTestsDir  = null;
    public $testsDir    = null;
    public $pluginDir   = null;

    /**
     * Constructor to the bootstrap file
     */
    public function __construct()
    {
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'localhost';
        }

        if (getenv('WP_MULTISITE')) {
            define('WP_TESTS_MULTISITE', 1);
        }

        $this->testsDir = dirname(__FILE__);
        $this->pluginDir = dirname($this->testsDir);
        $this->wpTestsDir = getenv('WP_TESTS_DIR')
            ? getenv('WP_TESTS_DIR')
            : '/tmp/wordpress-tests-lib';

        // Load test functions
        include_once $this->wpTestsDir . '/includes/functions.php';

        // Load WooCommerce
        tests_add_filter('muplugins_loaded', array($this, 'loadWooCommerce'));

        // Load the plugin
        tests_add_filter('muplugins_loaded', array($this, 'loadPlugin'));

        // Start up the WP testing environment.
        include_once $this->wpTestsDir . '/includes/bootstrap.php';

        // Include utility classes
        $this->includes();

        // Install the plugin
        $this->install();
    }

    /**
     * Loads the plugin for the current PHPUnit runtime
     *
     * @return void
     */
    public function loadPlugin()
    {
        include_once $this->pluginDir . '/gateway-securesubmit.php';
    }

    /**
     * Loads the WooCommerce plugin for the current PHPUnit runtime
     *
     * @return void
     */
    public function loadWooCommerce()
    {
        include_once $this->pluginDir . '/../woocommerce/woocommerce.php';
    }

    /**
     * Loads helper classes that aid in writing tests
     *
     * @return void
     */
    public function includes()
    {
        include_once $this->testsDir . '/utility/helper.php';
        include_once $this->testsDir . '/utility/installer.php';
        include_once $this->testsDir . '/utility/unit-test-case.php';

        include_once $this->testsDir . '/utility/order.php';
        include_once $this->testsDir . '/utility/product.php';
        include_once $this->testsDir . '/utility/shipping.php';
    }

    /**
     * Installs the plugin
     *
     * @return void
     */
    public function install()
    {
        update_option('woocommerce_currency', 'USD');
        WC_Gateway_SecureSubmit_Tests_Utility_Installer::installSecureSubmit();
    }
}
new WC_Gateway_SecureSubmit_Tests_Bootstrap();
