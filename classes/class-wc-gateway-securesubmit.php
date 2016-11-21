<?php

class WC_Gateway_SecureSubmit extends WC_Payment_Gateway
{
    private static $_instance = null;
    public $capture = null;
    public $payment = null;
    public $refund  = null;
    public $reverse = null;

    public function __construct()
    {
        // includes
        require_once 'includes/Hps.php';
        require_once 'wc-gateway-securesubmit/class-capture.php';
        require_once 'wc-gateway-securesubmit/class-payment.php';
        require_once 'wc-gateway-securesubmit/class-refund.php';
        require_once 'wc-gateway-securesubmit/class-reverse.php';

        // properties
        $this->id                      = 'securesubmit';
        $this->method_title            = __('SecureSubmit', 'wc_securesubmit');
        $this->icon                    = plugins_url('/assets/images/cards.png', dirname(__FILE__));
        $this->has_fields              = true;
        $this->initFormFields();
        $this->init_settings();
        $this->title                   = $this->getSetting('title');
        $this->description             = $this->getSetting('description');
        $this->enabled                 = $this->getSetting('enabled');
        $this->secret_key              = $this->getSetting('secret_key');
        $this->public_key              = $this->getSetting('public_key');
        $this->custom_error            = $this->getSetting('custom_error');
        $this->paymentaction           = $this->getSetting('paymentaction');
        $this->enable_anti_fraud       = ($this->getSetting('enable_anti_fraud') == 'yes' ? true : false);
        $this->allow_fraud             = $this->getSetting('allow_fraud');
        $this->email_fraud             = $this->getSetting('email_fraud');
        $this->fraud_address           = $this->getSetting('fraud_address');
        $this->fraud_text              = $this->getSetting('fraud_text');
        $this->fraud_velocity_attempts = $this->getSetting('fraud_velocity_attempts');
        $this->fraud_velocity_timeout  = $this->getSetting('fraud_velocity_timeout');
        $this->allow_card_saving       = ($this->getSetting('allow_card_saving') == 'yes' ? true : false);
        $this->use_iframes             = ($this->getSetting('use_iframes') == 'yes' ? true : false);
        $this->allow_gift_cards        = ($this->getSetting( 'gift_cards' ) == 'yes' ? true : false);
        $this->gift_card_title         = $this->getSetting( 'gift_cards_gateway_title' );
        $this->supports                = array(
                                            'products',
                                            'refunds'
                                         );

        // actions
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('admin_notices', array($this, 'checks'));
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('script_loader_tag', array($this, 'utf8'), 10, 2);

        // class references
        $this->capture = new WC_Gateway_SecureSubmit_Capture($this);
        $this->payment = new WC_Gateway_SecureSubmit_Payment($this);
        $this->refund  = new WC_Gateway_SecureSubmit_Refund($this);
        $this->reverse = new WC_Gateway_SecureSubmit_Reverse($this);
    }

    public static function instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function utf8($tag, $handle)
    {
        if (!in_array($handle, array('securesubmit', 'woocommerce_securesubmit'))) {
            return $tag;
        }
        return str_replace(' src', ' charset="utf-8" src', $tag);
    }

    public function checks()
    {
        global $woocommerce;

        if ($this->enabled == 'no') {
            return;
        }

        if (!$this->secret_key) {
            echo '<div class="error"><p>' . sprintf(__('SecureSubmit error: Please enter your secret key <a href="%s">here</a>', 'wc_securesubmit'), admin_url('admin.php?page=woocommerce&tab=payment_gateways&subtab=gateway-securesubmit')) . '</p></div>';
            return;
        } elseif (!$this->public_key) {
            echo '<div class="error"><p>' . sprintf(__('SecureSubmit error: Please enter your public key <a href="%s">here</a>', 'wc_securesubmit'), admin_url('admin.php?page=woocommerce&tab=payment_gateways&subtab=gateway-securesubmit')) . '</p></div>';
            return;
        }
    }

    public function is_available()
    {
        global $woocommerce;

        if ($this->enabled == "yes") {
            if ($woocommerce->version < '1.5.8') {
                return false;
            }

            // we will be adding more currencies in the near future, but today we are bound to USD
            if (!in_array(get_option('woocommerce_currency'), array('USD'))) {
                return false;
            }

            if (!$this->secret_key) {
                return false;
            }

            if (!$this->public_key) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function initFormFields()
    {
        $path = dirname(plugin_dir_path(__FILE__));
        $this->form_fields = include $path . '/etc/securesubmit-options.php';
    }

    public function admin_options()
    {
        $path = dirname(plugin_dir_path(__FILE__));
        include $path . '/templates/admin-options.php';
    }

    public function payment_fields()
    {
        $path = dirname(plugin_dir_path(__FILE__));
        include $path . '/templates/payment-fields.php';
    }

    public function payment_scripts()
    {
        if (!is_checkout()) {
            return;
        }

        $isCert = -1 !== strpos($this->public_key, '_cert_');
        $url = $isCert
            ? 'http://localhost:7777/dist/securesubmit.js' // 'https://hps.github.io/token/2.1/securesubmit.js'
            : 'https://api.heartlandportico.com/SecureSubmit.v1/token/2.1/securesubmit.js';

        // SecureSubmit tokenization library
        wp_enqueue_script('hps_wc_securesubmit_library', $url, array(), '2.1', true);
        // SecureSubmit js controller for WooCommerce
        wp_enqueue_script('woocommerce_securesubmit', plugins_url('assets/js/securesubmit.js', dirname(__FILE__)), array('jquery'), '1.0', true);
        // SecureSubmit custom CSS
        wp_enqueue_style('woocommerce_securesubmit', plugins_url('assets/css/securesubmit.css', dirname(__FILE__)), array(), '1.0');

        if (true) {
            $url = $isCert
                ? 'https://includestest.ccdc02.com/cardinalcruise/v1/songbird.js'
                : '';
            wp_enqueue_script('hps_wc_securesubmit_cardinal_library', $url, array(), '2.1', true);
        }

        $securesubmit_params = array(
            'key'         => $this->public_key,
            'use_iframes' => $this->use_iframes,
            'images_dir'  => plugins_url('assets/images', dirname(__FILE__)),
        );

        if (true) {
            $orderNumber = str_shuffle('abcdefghijklmnopqrstuvwxyz');
            $apiIdentifier = '579bc985da529378f0ec7d0e';
            $orgUnitId = '5799c3c433fadd4cf427d01a';
            $apiKey = 'a32ed153-3759-4302-a314-546811590b43';
            $data = array(
                'jti' => str_shuffle('abcdefghijklmnopqrstuvwxyz'),
                'iat' => time(),
                'iss' => $apiIdentifier,
                'OrgUnitId' => $orgUnitId,
                'Payload' => array(
                    'OrderDetails' => array(
                        'OrderNumber' => $orderNumber,
                        // Centinel requires amounts in pennies
                        'Amount' => 100 * WC()->cart->total,
                        'CurrencyCode' => '840',
                    ),
                ),
            );
            include_once 'class-heartland-jwt.php';
            $jwt = HeartlandJWT::encode($apiKey, $data);

            $securesubmit_params['cca'] = array(
                'jwt' => $jwt,
                'orderNumber' => $orderNumber,
            );
        }

        wp_localize_script('woocommerce_securesubmit', 'wc_securesubmit_params', $securesubmit_params);
    }

    public function process_payment($orderId)
    {
        return $this->payment->call($orderId);
    }

    public function process_capture($order)
    {
        if (!$this->isTransactionActiveOnGateway($order->id)) {
            $this->displayUserError('Payment already captured');
            return;
        }
        return $this->capture->call($order);
    }

    public function process_refund($orderId, $amount = null, $reason = '')
    {
        if ($this->isTransactionActiveOnGateway($orderId)) {
            return $this->reverse->call($orderId, $amount, $reason);
        } else {
            return $this->refund->call($orderId, $amount, $reason);
        }
    }

    public function getOrderTransactionId($order)
    {
        $transactionId = false;
        $args = array(
            'post_id' => $order->id,
            'approve' => 'approve',
        );

        remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'));

        $comments = get_comments($args);
        foreach ($comments as $comment) {
            if (strpos($comment->comment_content, '(Transaction ID: ') !== false) {
                $explodedComment = explode(': ', $comment->comment_content);
                $transactionId = substr($explodedComment[1], 0, -1); // trim) from comment
            }
        }
        unset($comments);
        unset($comment);

        add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'));
        return $transactionId;
    }

    public function throwUserError($error) {
        if ($customMessage = $this->custom_error) {
            $error = sprintf($customMessage, $error);
        }

        throw new Exception(__($error, 'wc_securesubmit'));
    }

    public function displayUserError($message)
    {
        global $woocommerce;
        $message = __((string)$message, 'wc_securesubmit');
        if (function_exists('wc_add_notice')) {
            wc_add_notice($message, 'error');
        } else if (isset($woocommerce) && property_exists($woocommerce, 'add_error')) {
            $woocommerce->add_error($message);
        }
    }

    public function getCreditService()
    {
        $config = new HpsServicesConfig();
        $config->secretApiKey = $this->secret_key;
        $config->versionNumber = '1510';
        $config->developerId = '002914';

        return new HpsCreditService($config);
    }

    public function getOrderAddress($order)
    {
        $hpsaddress = new HpsAddress();
        $hpsaddress->address = $order->billing_address_1;
        $hpsaddress->city = $order->billing_city;
        $hpsaddress->state = $order->billing_state;
        $hpsaddress->zip = preg_replace('/[^a-zA-Z0-9]/', '', $order->billing_postcode);
        $hpsaddress->country = $order->billing_country;
        return $hpsaddress;
    }

    public function getOrderCardHolder($order, $hpsaddress)
    {
        $cardHolder = new HpsCardHolder();
        $cardHolder->firstName = $order->billing_first_name;
        $cardHolder->lastName = $order->billing_last_name;
        $cardHolder->phone = preg_replace('/[^0-9]/', '', $order->billing_phone);
        $cardHolder->emailAddress = $order->billing_email;
        $cardHolder->address = $hpsaddress;
        return $cardHolder;
    }

    protected function isTransactionActiveOnGateway($orderId)
    {
        $order = wc_get_order($orderId);
        $transactionId = $this->getOrderTransactionId($order);
        $transaction = $this->getCreditService()->get($transactionId);
        return $transaction->transactionStatus == 'A';
    }

    protected function getSetting($setting)
    {
        $value = null;
        if (isset($this->settings[$setting])) {
            $value = $this->settings[$setting];
        }
        return $value;
    }
}
