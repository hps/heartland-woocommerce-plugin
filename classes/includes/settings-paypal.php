<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayPal Gateway
 */
return array(
	'enabled' => array(
		'title'   => __( 'Enable/Disable', 'wc_securesubmit' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable PayPal Secure Submit', 'wc_securesubmit' ),
		'default' => 'yes'
	),
	'title' => array(
		'title'       => __( 'Title', 'wc_securesubmit' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'wc_securesubmit' ),
		'default'     => __( 'PayPal', 'wc_securesubmit' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'wc_securesubmit' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'wc_securesubmit' ),
		'default'     => __( 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.', 'wc_securesubmit' )
	),
	'testmode' => array(
		'title'       => __( 'PayPal Sandbox', 'wc_securesubmit' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable PayPal sandbox', 'wc_securesubmit' ),
		'default'     => 'no',
		'description' => sprintf( __( 'PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'wc_securesubmit' ), 'https://developer.paypal.com/' ),
	),
	'debug' => array(
		'title'       => __( 'Debug Log', 'wc_securesubmit' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'wc_securesubmit' ),
		'default'     => 'no',
		'description' => sprintf( __( 'Adds error logging information to the following log file :  <code>%s</code>', 'wc_securesubmit' ), wc_get_log_file_path( 'paypal' ) )
	),
	'paymentaction' => array(
		'title'       => __( 'Payment Action', 'wc_securesubmit' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'wc_securesubmit' ),
		'default'     => 'sale',
		'desc_tip'    => true,
		'options'     => array(
			'sale'          => __( 'Capture', 'wc_securesubmit' ),
			'authorization' => __( 'Authorize', 'wc_securesubmit' )
		)
	),
    'public_key' => array(
	    'title'       => __( 'Public Key', 'wc_securesubmit' ),
	    'type'        => 'text',
	    'description' => __( 'Get your API keys from your SecureSubmit account.', 'wc_securesubmit' ),
	    'default'     => ''
    ),
    'secret_key' => array(
	    'title'       => __( 'Secret Key', 'wc_securesubmit' ),
	    'type'        => 'text',
	    'description' => __( 'Get your API keys from your SecureSubmit account.', 'wc_securesubmit' ),
	    'default'     => ''
    ),
);
