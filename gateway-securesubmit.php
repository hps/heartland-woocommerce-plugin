<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WooCommerce.
Version: 1.7.1
Author: SecureSubmit
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/

class WooCommerceSecureSubmitGateway
{
    const SECURESUBMIT_GATEWAY_CLASS = 'WC_Gateway_SecureSubmit';

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'), 0);
        add_action('woocommerce_load', array($this, 'activate'));
        add_action('wp_enqueue_scripts', array($this, 'loadScripts'));
    }

    public function init()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        load_plugin_textdomain('wc_securesubmit', false, dirname(plugin_basename(__FILE__)) . '/languages');

        $this->loadClasses();

        $securesubmit = call_user_func(array(self::SECURESUBMIT_GATEWAY_CLASS, 'instance'));
        add_filter('woocommerce_payment_gateways', array($this, 'addGateways'));
        add_action('woocommerce_after_my_account', array($this, 'savedCards'));
        add_action('woocommerce_order_actions', array($securesubmit->capture, 'addOrderAction'));
        add_action('woocommerce_order_action_' . $securesubmit->id . '_capture', array($securesubmit, 'process_capture'));

        $masterpass = call_user_func(array(self::SECURESUBMIT_GATEWAY_CLASS . '_MasterPass', 'instance'));
        add_action('wp_ajax_securesubmit_masterpass_lookup', array($masterpass, 'lookupCallback'));
        add_action('wp_ajax_nopriv_securesubmit_masterpass_lookup', array($masterpass, 'lookupCallback'));
        add_shortcode('woocommerce_masterpass_review_order', array($masterpass, 'reviewOrderShortcode'));

        add_action('woocommerce_order_actions', array($masterpass->capture, 'addOrderAction'));
        add_action('woocommerce_order_action_' . $masterpass->id . '_capture', array($masterpass, 'process_capture'));
        add_action('woocommerce_after_my_account', array($masterpass, 'myaccountConnect'));
        add_action('wp_loaded', array($masterpass->reviewOrder, 'processCheckout'));

        //paypal
        remove_action( 'init', 'woocommerce_paypal_express_review_order_page') ;
        remove_shortcode( 'woocommerce_review_order');
        add_shortcode( 'woocommerce_review_order', array($this, 'set_paypal_review_order_shortcode' ));
        add_action( 'init', array($this, 'check_url_for_paypal_parms') );
        add_action( 'wp_enqueue_scripts', array($this, 'set_paypal_init_styles'), 12 );
        add_action( 'woocommerce_after_cart', array($this, 'add_paypal_express_option'));

    }

    /**
     * Handle behaviors that only should occur at plugin activation.
     */
    public function activate()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        $this->loadClasses();
        call_user_func(array(self::SECURESUBMIT_GATEWAY_CLASS . '_MasterPass', 'createOrderReviewPage'));
        include_once('classes/class-wc-gateway-securesubmit.php');
        include_once('classes/class-wc-gateway-securesubmit-subscriptions.php');
        include_once('classes/class-wc-gateway-securesubmit-paypal.php');

        add_filter('woocommerce_payment_gateways', array($this, 'addGateway'));
        add_action('woocommerce_after_my_account', array($this, 'savedCards'));

        //paypal
        remove_action('init', 'woocommerce_paypal_express_review_order_page');
        remove_shortcode('woocommerce_review_order');
        add_shortcode('woocommerce_review_order', array($this, 'set_paypal_review_order_shortcode'));
        add_action('init', array($this, 'check_url_for_paypal_parms'));
        add_action('wp_enqueue_scripts', array($this, 'set_paypal_init_styles'), 12);
        add_action('woocommerce_after_cart', array($this, 'add_paypal_express_option'));
    }

    public function add_paypal_express_option()
    {
        $ssPayPal = new WC_Gateway_SecureSubmit_PayPal();
        if ($ssPayPal->enabled == 'yes') {
            WC()->session->set('checkout_form', $_POST);
            $ssGateway = new WC_Gateway_SecureSubmit_PayPal();
            if (isset($_POST["paypalexpress_initiated"])) {
                $ssGateway->process_paypal_express_payment_checkout();
            } else {
                echo $this->paypal_express_button_html();
            };
        };
    }

    public function paypal_express_button_html()
    {
        $URI = trim(get_permalink());
        if (substr($URI, -1) == '/') {
            $URI = substr($URI, 0, strlen($URI) - 1);
        };
        $html = "<div style='float: right'><form id='pp' method='post' action='$URI?paypalexpress_initiated=true'>";
        $html = $html . "<input type='hidden' name='paypalexpress_initiated' value='true'/>";
        $html = $html . "<a href='#' onclick=\"document.getElementById('pp').submit();\">";
        $html = $html . "<img src='https://www.paypalobjects.com/en_US/i/btn/x-click-but6.gif'>";
        $html = $html . "</a></form></div>";
        return $html;
    }

    /**
     * Adds payment options to WooCommerce to be enabled by store admin.
     *
     * @param array $methods
     *
     * @return array
     */
    public function addGateways($methods)
    {
        $methods[] = self::SECURESUBMIT_GATEWAY_CLASS . '_MasterPass';
        if (class_exists('WC_Subscriptions_Order')) {
            $klass = self::SECURESUBMIT_GATEWAY_CLASS . '_Subscriptions';
            if (!function_exists('wcs_create_renewal_order')) {
                $klass .= '_Deprecated';
            }
            $methods[] = $klass;
        } else {
            $methods[] = self::SECURESUBMIT_GATEWAY_CLASS;
        }
        $methods[] = 'WC_Gateway_SecureSubmit_PayPal';
        return $methods;
    }

    /**
     * Handles "Manage saved cards" interface to user.
     */
    public function savedCards()
    {
        $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

        if (!$cards) {
            return;
        }

        if (isset($_POST['delete_card']) && wp_verify_nonce($_POST['_wpnonce'], "secure_submit_del_card")) {
            $card = $cards[(int)$_POST['delete_card']];
            delete_user_meta(get_current_user_id(), '_secure_submit_card', $card);
        }

        if (!$cards) {
            return;
        }

        $path = plugin_dir_path(__FILE__);
        include $path . 'templates/saved-cards.php';
    }

    public function loadScripts()
    {
        if (!is_account_page()) {
            return;
        }
        // SecureSubmit custom CSS
        wp_enqueue_style('woocommerce_securesubmit', plugins_url('assets/css/securesubmit.css', __FILE__), array(), '1.0');
    }

    protected function loadClasses()
    {
        include_once('classes/class-wc-gateway-securesubmit.php');
        include_once('classes/class-wc-gateway-securesubmit-paypal.php');
        include_once('classes/class-wc-gateway-securesubmit-subscriptions.php');
        include_once('classes/class-wc-gateway-securesubmit-subscriptions-deprecated.php');
        include_once('classes/class-wc-gateway-securesubmit-masterpass.php');
    }

    //PayPal
    function set_paypal_init_styles()
    {
        if (is_page(wc_get_page_id('review_order'))) {
            $assets_path = str_replace(array('http:', 'https:'), '', WC()->plugin_url()) . '/assets/';
            $frontend_script_path = $assets_path . 'js/frontend/';
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script('wc-checkout', plugins_url('/assets/js/checkout.js', __FILE__), array('jquery'), WC_VERSION, true);

            wp_localize_script('wc-checkout', 'wc_checkout_params', apply_filters('wc_checkout_params', array(
                'ajax_url' => WC()->ajax_url(),
                'ajax_loader_url' => apply_filters('woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif'),
                'update_order_review_nonce' => wp_create_nonce("update-order-review"),
                'apply_coupon_nonce' => wp_create_nonce("apply-coupon"),
                'option_guest_checkout' => get_option('woocommerce_enable_guest_checkout'),
                'checkout_url' => add_query_arg('action', 'woocommerce_checkout', WC()->ajax_url()),
                'is_checkout' => 1
            )));
        }
    }

    function check_url_for_paypal_parms()
    {
        if (!empty($_GET['pp_action']) && $_GET['pp_action'] == 'revieworder') {
            $secureSubmitPayPalGateway = new WC_Gateway_SecureSubmit_PayPal();
            $secureSubmitPayPalGateway->process_paypal_checkout();
        }
    }

    function set_paypal_review_order_shortcode( $atts ) {
        global $woocommerce;
        return WC_Shortcodes::shortcode_wrapper(array($this, 'get_paypal_review_order_page'), $atts);
    }

    function get_paypal_review_order_page() {
        global $woocommerce;

        wc_print_notices();
        echo "
	    <script>
	    jQuery(document).ready(function($) {
		    // Inputs/selects which update totals instantly
            $('form.checkout').unbind( 'submit' );
	    });
	    </script>
	    ";

        //Allow override in theme: <theme_name>/woocommerce/paypal-paypal-review-order.php
        $template = plugin_dir_path(__FILE__) . 'templates/';
        wc_get_template('paypal-review-order.php', array(), '', $template);
        do_action('woocommerce_ppe_checkout_order_review');
    }
}
new WooCommerceSecureSubmitGateway();
