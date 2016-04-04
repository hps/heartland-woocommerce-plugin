<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayPal Gateway
 */
return array(
	'enabled' => array(
		'title'   => __( 'Enable/Disable', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable PayPal Secure Submit', 'woocommerce' ),
		'default' => 'yes'
	),
	'title' => array(
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'PayPal', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.', 'woocommerce' )
	),
	'testmode' => array(
		'title'       => __( 'PayPal Sandbox', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable PayPal sandbox', 'woocommerce' ),
		'default'     => 'no',
		'description' => sprintf( __( 'PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'woocommerce' ), 'https://developer.paypal.com/' ),
	),
	'debug' => array(
		'title'       => __( 'Debug Log', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'woocommerce' ),
		'default'     => 'no',
		'description' => sprintf( __( 'Adds error logging information to the php error log file.Log PayPal events, such as IPN requests, inside <code>%s</code>', 'woocommerce' ), wc_get_log_file_path( 'paypal' ) )
	),
	'paymentaction' => array(
		'title'       => __( 'Payment Action', 'woocommerce' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce' ),
		'default'     => 'sale',
		'desc_tip'    => true,
		'options'     => array(
			'sale'          => __( 'Capture', 'woocommerce' ),
			'authorization' => __( 'Authorize', 'woocommerce' )
		)
	),
    'public_key' => array(
	    'title'       => __( 'Public Key', 'woocommerce' ),
	    'type'        => 'text',
	    'description' => __( 'Get your API keys from your SecureSubmit account.', 'woocommerce' ),
	    'default'     => ''
    ),
    'secret_key' => array(
	    'title'       => __( 'Secret Key', 'woocommerce' ),
	    'type'        => 'text',
	    'description' => __( 'Get your API keys from your SecureSubmit account.', 'woocommerce' ),
	    'default'     => ''
    ),
);
