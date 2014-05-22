<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: A payment gateway for SecureSubmit (http://developer.heartlandpaymentsystems.com/SecureSubmit). A SecureSubmit account and a valid SSL certificate is required (for security reasons) for this gateway to function.
Version: 1.0.0
Author: Heartland Payment Systems
Author URI: http://developer.heartlandpaymentsystems.com/SecureSubmit
*/

add_action( 'plugins_loaded', 'woocommerce_securesubmit_init', 0 );

function woocommerce_securesubmit_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) )
		return;

	load_plugin_textdomain( 'wc_securesubmit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	include_once( 'classes/class-wc-gateway-securesubmit.php' );

	function add_securesubmit_gateway($methods) {
		$methods[] = 'WC_Gateway_SecureSubmit';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_securesubmit_gateway' );
}
