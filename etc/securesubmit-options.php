<?php

if (!defined('ABSPATH')) {
    exit();
}

return array(
    'enabled' => array(
        'title'       => __('Enable/Disable', 'wc_securesubmit'),
        'label'       => __('Enable SecureSubmit', 'wc_securesubmit'),
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no',
    ),
    'title' => array(
        'title'       => __('Title', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('This controls the title the user sees during checkout.', 'wc_securesubmit'),
        'default'     => __('Credit Card', 'wc_securesubmit'),
    ),
    'description' => array(
        'title'       => __('Description', 'wc_securesubmit'),
        'type'        => 'textarea',
        'description' => __('This controls the description the user sees during checkout.', 'wc_securesubmit'),
        'default'     => 'Pay with your credit card via SecureSubmit.',
    ),
    'public_key' => array(
        'title'       => __('Public Key', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('Get your API keys from your SecureSubmit account.', 'wc_securesubmit'),
        'default'     => '',
    ),
    'secret_key' => array(
        'title'       => __('Secret Key', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('Get your API keys from your SecureSubmit account.', 'wc_securesubmit'),
        'default'     => '',
    ),
    'custom_error' => array(
        'title'       => __('Custom Error', 'wc_securesubmit'),
        'type'        => 'textarea',
        /* translators: %s: Secure Submit Error example */
        'description' => __('To use the default Secure Submit error message use %1$s in the custom message text, ex. My message. %2$s -> will be displayed as: My message. Original Secure Submit message.', 'wc_securesubmit'),
        'default'     => '%s',
    ),
    'allow_card_saving' => array(
        'title'       => __('Allow Card Saving', 'wc_securesubmit'),
        'label'       => __('Allow Card Saving', 'wc_securesubmit'),
        'type'        => 'checkbox',
        'description' => 'Note: to use the card saving feature, you must have multi-use tokenization enabled on your Heartland account.',
        'default'     => 'no',
    ),
    'enable_anti_fraud' => array(
        'title'       => __('Enable Anti-Fraud Controls', 'wc_securesubmit'),
        'type'        => 'checkbox',
        'default'     => 'yes',
        'class'       => 'enable-anti-fraud',
    ),
    'allow_fraud' => array(
        'title'       => __('Allow Suspicious', 'wc_securesubmit'),
        'label'       => __('Do not fail suspicious orders', 'wc_securesubmit'),
        'type'        => 'checkbox',
        'description' => 'Note: You will have 72 hours from the original authorization date to manually review suspicious orders in the virtual terminal and make a final decision (either to accept the gateway fraud decision or to manually override).',
        'default'     => 'no',
        'class'       => 'anti-fraud',
    ),
    'email_fraud' => array(
        'title'       => __('Email Suspicious', 'wc_securesubmit'),
        'label'       => __('Email store owner on suspicious orders', 'wc_securesubmit'),
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no',
        'class'       => 'anti-fraud',
    ),
    'fraud_address' => array(
        'title'       => __('Notification Email Address', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('This email address will be notified of suspicious orders.', 'wc_securesubmit'),
        'default'     => esc_html('', 'wc_securesubmit'),
        'class'       => 'anti-fraud',
    ),
    'fraud_text' => array(
        'title'       => __('Fraud Text', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('This is the text that will display to the customer when fraud is detected and the transaction fails.', 'wc_securesubmit'),
        'default'     => __('Please call customer service.', 'wc_securesubmit'),
        'class'       => 'anti-fraud',
    ),
    'fraud_velocity_attempts' => array(
        'title'       => __('Max Velocity Attempts', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('The maximum number of attempts allowed per unique IP address before additional attempts from user are blocked.', 'wc_securesubmit'),
        'default'     => __('3', 'wc_securesubmit'),
        'class'       => 'anti-fraud',
    ),
    'fraud_velocity_timeout' => array(
        'title'       => __('Velocity Timespan', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('The amount of time (in minutes) before recent failures are ignored.', 'wc_securesubmit'),
        'default'     => __('10', 'wc_securesubmit'),
        'class'       => 'anti-fraud',
    ),
    'paymentaction' => array(
        'title'       => __('Payment Action', 'wc_securesubmit'),
        'type'        => 'select',
        'description' => __('Choose whether you wish to capture funds immediately, authorize payment only for a delayed capture or verify and capture when the order ships.', 'wc_securesubmit'),
        'default'     => 'sale',
        'desc_tip'    => true,
        'options'     => array(
            'sale'          => __('Capture', 'wc_securesubmit'),
            'authorization' => __('Authorize', 'wc_securesubmit'),
            'verify'        => __('Verify', 'wc_securesubmit'),
        ),
    ),
    'txndescriptor' => array(
        'title'       => __('Order Transaction Descriptor', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('During a Capture or Authorize payment action, this value will be passed along as the TxnDescriptor. Please contact <a href="mailto:securesubmitcert@e-hps.com?Subject=WooCommerce%20SecureSubmit%20TxnDescriptor Option">securesubmitcert@e-hps.com</a> with any question regarding this option.', 'wc_securesubmit'),
        'default'     => esc_html('', 'wc_securesubmit'),
        'class'       => 'txndescriptor',
        'custom_attributes' => array(
            'maxlength' => 18,
        ),
    ),
    'gift_cards' => array(
        'title'       => __('Enable Gift Cards', 'wc_securesubmit'),
        'label'       => __('Allow customers to use gift cards to pay for purchases in full or in part.', 'wc_securesubmit'),
        'type'        => 'checkbox',
        'description' => 'This will display a gift card entry field in the checkout above the credit card entry area.',
        'default'     => 'no',
        'class'       => 'enable-gift',
    ),
    'gift_cards_gateway_title' => array(
        'title'       => __('Gift Card Title', 'wc_securesubmit'),
        'label'       => __('This controls the payment method name users see when gift cards are enabled.', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => 'This updates the payment method title on checkout if you have enabled gift card usage.',
        'default'     => 'Credit and/or Gift Cards',
        'class'       => 'gift',
    ),
    'gift_cards_gateway_description' => array(
        'title'       => __('Gift Card Description', 'wc_securesubmit'),
        'label'       => __('This controls the payment method description users see when gift cards are enabled.', 'wc_securesubmit'),
        'type'        => 'textarea',
        'description' => 'This is the description that will display in the payment area of checkout if gift cards are enabled.',
        'default'     => 'Pay with your credit or gift card via SecureSubmit.',
        'class'       => 'gift',
    ),
    'app_id' => array(
        'title'       => __('App ID', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('For use with Transaction Optimization', 'wc_securesubmit'),
        'default'     => '',
    ),
    'app_key' => array(
        'title'       => __('App Key', 'wc_securesubmit'),
        'type'        => 'text',
        'description' => __('For use with Transaction Optimization', 'wc_securesubmit'),
        'default'     => '',
    )
);
