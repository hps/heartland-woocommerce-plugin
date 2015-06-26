<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WooCommerce.
Version: 1.2.1
Author: SecureSubmit
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/

class WooCommerceSecureSubmitGateway
{
    public function __construct()
    {
            add_action('plugins_loaded', array($this, 'init'), 0);
    }

    public function init()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        load_plugin_textdomain('wc_securesubmit', false, dirname(plugin_basename(__FILE__)) . '/languages');

        include_once('classes/class-wc-gateway-securesubmit.php');
        include_once('classes/class-wc-gateway-securesubmit-subscriptions.php');
        include_once('classes/class-wc-gateway-securesubmit-paypal.php');

        add_filter('woocommerce_payment_gateways', array($this, 'addGateway'));
        add_action('woocommerce_after_my_account', array($this, 'savedCards'));  
        
        //paypal  
        remove_action( 'init', 'woocommerce_paypal_express_review_order_page') ;   
        remove_shortcode( 'woocommerce_review_order');
        add_shortcode( 'woocommerce_review_order', array($this, 'set_paypal_review_order_shortcode' ));
        add_action( 'init', array($this, 'process_paypal_review_order_page') );
        add_action( 'wp_enqueue_scripts', array($this, 'set_paypal_init_styles'), 12 );        
    }
   
    public function addGateway($methods)
    {
        if (class_exists('WC_Subscriptions_Order')) {
            $methods[] = 'WC_Gateway_SecureSubmit_Subscriptions';
        } else {
            $methods[] = 'WC_Gateway_SecureSubmit';
        }
        $methods[] = 'WC_Gateway_SecureSubmit_PayPal';
        return $methods;
    }

    public function savedCards()
    {
        $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false);

        if (!$cards) {
            return;
        }

        if (isset($_POST['delete_card']) && wp_verify_nonce($_POST['_wpnonce'], "secure_submit_del_card")) {
            $card = $cards[(int) $_POST['delete_card']];
            delete_user_meta(get_current_user_id(), '_secure_submit_card', $card);
        }

        if (!$cards) {
            return;
        }
        
        $path = plugin_dir_path(__FILE__);
        include $path . 'templates/saved-cards.php';
    }
    
    
    //PayPal
    function set_paypal_init_styles() 
    {
        if (is_page( wc_get_page_id( 'review_order' ) )) 
        {
            $assets_path          = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
            $frontend_script_path = $assets_path . 'js/frontend/';
            $suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script( 'wc-checkout', plugins_url( '/assets/js/checkout.js' , __FILE__ ), array( 'jquery' ), WC_VERSION, true );

            wp_localize_script( 'wc-checkout', 'wc_checkout_params', apply_filters( 'wc_checkout_params', array(
                'ajax_url'                  => WC()->ajax_url(),
                'ajax_loader_url'           => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
                'update_order_review_nonce' => wp_create_nonce( "update-order-review" ),
                'apply_coupon_nonce'        => wp_create_nonce( "apply-coupon" ),
                'option_guest_checkout'     => get_option( 'woocommerce_enable_guest_checkout' ),
                'checkout_url'              => add_query_arg( 'action', 'woocommerce_checkout', WC()->ajax_url() ),
                'is_checkout'               => 1
            ) ) );
        }
    }
    
    function process_paypal_review_order_page() 
    {
        if ( ! empty( $_GET['pp_action'] ) && $_GET['pp_action'] == 'revieworder' ) {
            $secureSubmitPayPalGateway = new WC_Gateway_SecureSubmit_PayPal();
            $secureSubmitPayPalGateway->process_paypal_checkout();
        }
    }
    
    function set_paypal_review_order_shortcode( $atts ) {
        global $woocommerce;
        return WC_Shortcodes::shortcode_wrapper(array($this,'get_paypal_review_order_page'), $atts);
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
        $template = plugin_dir_path( __FILE__ ) . 'templates/';
        wc_get_template('paypal-review-order.php', array(), '', $template);
        do_action( 'woocommerce_ppe_checkout_order_review' );
    }

}
new WooCommerceSecureSubmitGateway();
