<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WooCommerce.
Version: 1.3.5
Author: SecureSubmit
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/

class WooCommerceSecureSubmitGateway
{
    const SECURESUBMIT_GATEWAY_CLASS = 'WC_Gateway_SecureSubmit';
    const MASTERPASS_GATEWAY_CLASS = self::SECURESUBMIT_GATEWAY_CLASS . '_MasterPass';
    const SUBSCRIPTIONS_GATEWAY_CLASS = self::SECURESUBMIT_GATEWAY_CLASS . '_Subscriptions';

    public function __construct()
    {
            add_action('plugins_loaded', array($this, 'init'), 0);
            register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        load_plugin_textdomain('wc_securesubmit', false, dirname(plugin_basename(__FILE__)) . '/languages');

        include_once('classes/class-wc-gateway-securesubmit.php');
        include_once('classes/class-wc-gateway-securesubmit-subscriptions.php');
        include_once('classes/class-wc-gateway-securesubmit-masterpass.php');

        add_filter('woocommerce_payment_gateways', array($this, 'addGateways'));
        add_action('woocommerce_after_my_account', array($this, 'savedCards'));

        $masterpass = call_user_func(array(self::MASTERPASS_GATEWAY_CLASS, 'instance'));
        add_action('wp_ajax_securesubmit_masterpass_lookup', array($masterpass, 'lookupCallback'));
        add_action('wp_ajax_nopriv_securesubmit_masterpass_lookup', array($masterpass, 'lookupCallback'));
        add_shortcode('woocommerce_masterpass_review_order', array($masterpass, 'reviewOrderShortcode'));
        add_action('woocommerce_order_actions', array($masterpass->capture, 'addOrderAction'));
        add_action('woocommerce_order_action_' . $masterpass->id . '_capture', array($masterpass, 'process_capture'));
        add_action('woocommerce_after_my_account', array($masterpass, 'myaccountConnect'));
        add_action('wp_loaded', array($masterpass->reviewOrder, 'processCheckout'));
    }

    /**
     * Handle behaviors that only should occur at plugin activation.
     */
    public function activate()
    {
        call_user_func(array(self::MASTERPASS_GATEWAY_CLASS, 'createOrderReviewPage'));
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
        $methods[] = self::MASTERPASS_GATEWAY_CLASS;
        if (class_exists('WC_Subscriptions_Order')) {
            $methods[] = self::SUBSCRIPTIONS_GATEWAY_CLASS;
        } else {
            $methods[] = self::SECURESUBMIT_GATEWAY_CLASS;
        }
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
            $card = $cards[(int) $_POST['delete_card']];
            delete_user_meta(get_current_user_id(), '_secure_submit_card', $card);
        }

        if (!$cards) {
            return;
        }

        $path = plugin_dir_path(__FILE__);
        include $path . 'templates/saved-cards.php';
    }
}
new WooCommerceSecureSubmitGateway();
