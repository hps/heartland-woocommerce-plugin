<?php
/*
Plugin Name: WooCommerce Heartland PayPal Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland PayPal Payment gateway for WooCommerce.
Version: 1.0.0
Author: SecureSubmit
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_SecureSubmit_PayPal extends WC_Payment_Gateway {

    /** @var boolean Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;
    
    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id                 = 'paypal';
        $this->has_fields         = false;
        $this->order_button_text  = __( 'Proceed to PayPal', 'wc_securesubmit' );
        $this->method_title       = __( 'Heartland Paypal', 'wc_securesubmit' );
        $this->method_description = __( 'PayPal works by sending customers to PayPal where they can enter their payment information.', 'woocommerce' );
        $this->supports           = array(
            'products',
            'refunds'
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title          = $this->get_option( 'title' );
        $this->description    = $this->get_option( 'description' );
        $this->testmode       = 'yes' === $this->get_option( 'testmode', 'no' );
        $this->debug          = 'yes' === $this->get_option( 'debug', 'no' );
        $this->paymentaction  = $this->get_option( 'paymentaction' );
        $this->public_key     = $this->get_option( 'public_key' );
        $this->secret_key     = $this->get_option( 'secret_key' );
        $this->enabled        = $this->get_option( 'enabled' );

        self::$log_enabled    = $this->debug;

        add_action( 'woocommerce_api_' . strtolower( get_class() ), array( $this, 'process_paypal_checkout' ), 12 );
        add_action( 'woocommerce_receipt_paypal_express', array( $this, 'receipt_page' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );       
    }
    

    /**
     * Logging method
     * @param  string $message
     */
    public static function debug_log( $message ) {
        if ( self::$log_enabled ) {
            if ( empty( self::$log ) ) {
                self::$log = new WC_Logger();
            }
            self::$log->add( 'heartland-paypal', $message );
        }
    }

    /**
     * get_icon function.
     *
     * @return string
     */
    public function get_icon() {
        $icon_html = '<img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg" alt="' . __( 'PayPal Acceptance Mark', 'wc_securesubmit' ) . '" />';
        $icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
        $icon_html .= sprintf( '<a href="%1$s" class="about_paypal" onclick="javascript:window.open(\'%1$s\',\'WIPaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is PayPal?', 'wc_securesubmit' ) . '">' . esc_attr__( 'What is PayPal?', 'wc_securesubmit' ) . '</a>', esc_url('https://www.paypal.com/us/webapps/mpp/paypal-popup'));
        return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     *
     * @return bool
     */
    public function is_valid_for_use() {
        return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paypal_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) );
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function admin_options() {
        if ( $this->is_valid_for_use() ) {
            parent::admin_options();
        } else {
            ?>
            <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'PayPal does not support your store currency.', 'woocommerce' ); ?></p></div>
            <?php
        }
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = include( 'includes/settings-paypal.php' );
    }

    /**
     * Get the transaction URL.
     *
     * @param  WC_Order $order
     *
     * @return string
     */
    public function get_transaction_url( $order ) {
        if ( $this->testmode ) {
            $this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        } else {
            $this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        }
        return parent::get_transaction_url( $order );
    }

    public function process_paypal_express_payment_checkout() {
        self::debug_log('Begin function : ' . __FUNCTION__);
        global $woocommerce, $post;
        $this->set_session('ss-paypal-express-checkout-inprogress', true);
        $this->set_session('checkout_form', $_POST);
        self::debug_log('$_POST = ' . print_r($_POST,true));
        $hpsBuyer = new HpsBuyerData();
        $review_order_page_url = add_query_arg( 'pp_action','revieworder', $this->get_review_order_page() );
        $hpsBuyer->returnUrl = $review_order_page_url;
        $hpsBuyer->cancelUrl = wc_get_cart_url();
        $hpsLineItems = $this->get_cart_lineitems();  //@review
        $payment = $this->get_cart_paymentdata();
        $porticoService = $this->getPorticoService();
        $redirectUrl = null;
        try
        {
            $response = $porticoService->createSession(WC()->cart->total,"USD", $hpsBuyer, $payment, null, $hpsLineItems);
            $redirectUrl = $response->redirectUrl;
        }
        catch (Exception $e)
        {
            $error = __('Error creating PayPal Portico session:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            self::debug_log('Portico session creation failedException redirect url = ' . $redirectUrl);
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            $redirectUrl = wc_get_cart_url();
            echo('<script type="application/javascript">window.location.href="'.$redirectUrl.'";</script>');
            wp_redirect($redirectUrl);
            exit();
        }

        echo('<script type="application/javascript">window.location.href="'.$redirectUrl.'";</script>');
        self::debug_log('$redirectUrl = ' . $redirectUrl);
        self::debug_log('End function : ' . __FUNCTION__);
        self::debug_log(' ------------------------------------------------- [ SENDING TO PAYPAL ]');
        exit();
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        self::debug_log('Begin function : ' . __FUNCTION__);
        self::debug_log('$order_id = ' . $order_id);
        $this->set_session('ss_order_id', $order_id);
        $override = WC()->session->get('process_payment_override');
        if( isset($override) && $override==1)
        {
            $this->set_session('process_payment_override',0);
            $this->set_session('process_payment_override_order_id', $order_id);
            self::debug_log('process_payment quick exit process payment');
            self::debug_log('End function [a]: ' . __FUNCTION__);
            return;
        }
        global $woocommerce, $post;
        $this->set_session('checkout_form', $_POST);
        $order = wc_get_order( $order_id );

        // create portico session if needed
        $isExpressCheckout = WC()->session->get('ss-paypal-express-checkout-inprogress');
        self::debug_log('$isExpressCheckout = ' . $isExpressCheckout);
        if( !isset($expressCheckout) || $isExpressCheckout == false) {
            $porticoService = $this->getPorticoService();

            $shippingInfo = $this->getShippingInfo($order);
            $buyer = $this->get_buyer_data($order);
            $payment = $this->get_payment_data($order);
            $lineItems = $this->getLineItems($order);

            $orderTotal = $order->order_total;
            $currency = strtolower(get_woocommerce_currency());


            //call portico to create session
            $response;
            try {
                $response = $porticoService->createSession($orderTotal, $currency, $buyer, $payment, $shippingInfo, $lineItems);
            } catch (Exception $e) {
                $error = __('Error creating PayPal Portico session:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
                if (function_exists('wc_add_notice')) {
                    wc_add_notice($error, 'error');
                } else {
                    $woocommerce->add_error($error);
                }
                return array(
                    'result' => 'fail',
                    'redirect' => '');
            }
            $redirectUrl = $response->redirectUrl;
            self::debug_log('Process_Payment normal exit to url : ' . $redirectUrl);
            self::debug_log('End function [b]: ' . __FUNCTION__);
            return array(
                'result' => 'success',
                'redirect' => $redirectUrl
            );
            self::debug_log('End function [c]: ' . __FUNCTION__);
        } else {
            $this->set_session('ss-paypal-express-checkout-inprogress',null);
        }
    }

    /**
     * Can the order be refunded via PayPal?
     * @param  WC_Order $order
     * @return bool
     */
    public function can_refund_order( $order ) {
        return $order && $order->get_transaction_id();
    }

    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  boolean True or false based on success, or a WP_Error object
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {

        $order = wc_get_order( $order_id );

        if ( ! $this->can_refund_order( $order ) ) {
            self::debug_log( 'Refund Failed: No transaction ID' );
            return false;
        }
        $response = null;
        try {
            $porticoService = $this->getPorticoService();
            $isPartial = isset($amount) && $amount > 0;
            $response = $porticoService->refund($order->get_transaction_id(), $isPartial, $amount);

        } catch (Exception $e) {
            $error = __('Error processing refund:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            self::debug_log('process_refund : $error = ' . $error);
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            return false;
        }

        self::debug_log('process_refund : $response = ' . print_r($response,true));
        if ($response->responseCode=="00") {
            $reason = $reason == '' ? '' : '. Reason for refund: '.$reason;
            $order->add_order_note( __( 'Heartland PayPal refund completed. Transaction id: ' . $response->transactionId . $reason, 'wc_securesubmit' ));
            return true;
        }

        self::debug_log( 'Refund Failed in Portico call with responseCode ' . $response->responseCode);
        return false;
    }
    
    protected function getPorticoService()
    {
        $config = new HpsServicesConfig();
        if($this->testmode)
        {
            $config->username  = '30360021';
            $config->password  = '$Test1234';
            $config->deviceId  = '90911395';
            $config->licenseId = '20527';
            $config->siteId    = '20518';
            $config->versionNumber = '1510';
            $config->developerId = '002914';
            $config->soapServiceUri  = "https://api-uat.heartlandportico.com/paymentserver.v1/PosGatewayService.asmx";
        }
        else
        {
            $config->secretApiKey = $this->secret_key;
            $config->publicApiKey = $this->public_key;
        }
        
        return new HpsPayPalService($config);
    }

    protected function getShippingInfo($order)
    {
        $shippingInfo = new HpsShippingInfo();        
        $shippingInfo->name = $order->shipping_first_name . ' ' . $order->shipping_last_name;
        $shippingInfo->address = new HpsAddress();
        $shippingInfo->address->address = $order->shipping_address_1;
        $shippingInfo->address->city = $order->shipping_city;
        $shippingInfo->address->state = $order->shipping_state;
        $shippingInfo->address->zip = preg_replace('/[^a-zA-Z0-9]/', '', $order->shipping_postcode);
        $shippingInfo->address->country = $order->shipping_country;
        
        return $shippingInfo;
    }
    
    protected function getLineItems($order)
    {
        $lineItems = array();
        $calculated_total = 0;
        
        foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			if ( 'fee' === $item['type'] ) {
				$lineItem          = $this->createLineItem( $item['name'], 1, $item['line_total'] );
				$calculated_total += $item['line_total'];
			} else {
				$product           = $order->get_product_from_item( $item );
				$lineItem          = $this->createLineItem( $this->get_order_item_name( $order, $item ), $item['qty'], $order->get_item_subtotal( $item, false ), $product->get_sku(), $order->get_item_tax($item) );
				$calculated_total += $order->get_item_subtotal( $item, false ) * $item['qty'];
			}

			if ( ! $lineItem ) {
				continue;
			}
            
            $lineItems[] = $lineItem;
		}
        
        // Check for mismatched totals
		if ( wc_format_decimal( $calculated_total + $order->get_total_tax() + round( $order->get_total_shipping(), 2 ) - round( $order->get_total_discount(), 2 ), 2 ) != wc_format_decimal( $order->get_total(), 2 ) ) {
			return false;
		}
        
        return $lineItems;
    }
    
    protected function createLineItem( $item_name, $quantity = 1, $amount = 0, $item_number = '', $item_tax = 0 ) 
    {
		if ( ! $item_name || $amount < 0 || $quantity < 0 || $item_tax < 0 ) {
			return false;
		}
        
        $line_item = new HpsLineItem();
        $line_item->name = html_entity_decode( wc_trim_string( $item_name, 127 ), ENT_NOQUOTES, 'UTF-8' );
        $line_item->number = $item_number;
        $line_item->amount = $amount;
        $line_item->quantity = $quantity;
        $line_item->taxAmount = $item_tax;
        
        //$this->self::debug_log('name: ' . $line_item->name .
        //      '; number: ' . $item_number . '; amount: ' .  $amount . '; quantity: ' . $quantity . '; tax: ' . $item_tax                   
        //);

		return $line_item;
    }
    
    protected function get_order_item_name( $order, $item )
    {
	    $item_name = $item['name'];
	    $item_meta = new WC_Order_Item_Meta( $item['item_meta'] );

	    if ( $meta = $item_meta->display( true, true ) ) {
		    $item_name .= ' ( ' . $meta . ' )';
	    }

	    return $item_name;
	}
    
     /**
     *  Process PayPal Checkout
     *
     *  Main action function that handles PPE actions:
     *  1. 'revieworder' - Customer has reviewed the order. Saves shipping info to order.
     *  2. 'payaction' - Customer has pressed "Place Order" on the review page.
     */
    public function process_paypal_checkout($posted = null)
    {
        self::debug_log('Begin function : ' . __FUNCTION__);
        self::debug_log('$_GET = ' . print_r($_GET,true));
        if ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'revieworder' )
		{
            $this->paypal_review_order();
        }
        elseif ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'payaction' )
        {
            $this->paypal_finalize_order();
        }
        self::debug_log('Exiting function : ' . __FUNCTION__);
    }

    
    private function paypal_review_order()
    {
        self::debug_log('Begin function : ' . __FUNCTION__);
        wc_clear_notices();
        // The customer has logged into PayPal and approved order.
        // Retrieve the shipping details and present the order for completion.
        if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
            define( 'WOOCOMMERCE_CHECKOUT', true );
        if ( isset( $_GET['token'] ) ) 
        {
            self::debug_log('Setting session TOKEN value to ' . $_GET['token']);
            $token = $_GET['token'];
            $this->set_session( 'TOKEN', $token );
        } else {
            // Raise exception here since no data was returned from paypal
        }
        
        //get sessioninfo from portico in case any changes are made on PayPal's site, HpsAltPaymentSessionInfo return type
        $porticoService = $this->getPorticoService(); 
        $porticoSessionInfo = $porticoService->sessionInfo($token);
        $shippingInfo = $porticoSessionInfo->shipping;

        if(!empty($porticoSessionInfo))
        {
            $this->set_session('RESULT',serialize($porticoSessionInfo));
            if ( isset( $shippingInfo->address->country ) ) {
                /**
                 * Check if shiptocountry is in the allowed countries list
                 */
                if (!array_key_exists($shippingInfo->address->country, WC()->countries->get_allowed_countries())) {
                    wc_add_notice(  sprintf( __('We do not sell in your country, please try again with another address.', 'wc_securesubmit' ) ), 'error' );
                    wp_redirect( get_permalink( wc_get_page_id( 'cart' ) ) );
                    exit;
                };
                WC()->customer->set_shipping_country( $shippingInfo->address->country );
            }
        }
        else
        {
            self::debug_log( "...ERROR: GetShippingDetails returned empty result" );
        }

        self::debug_log('End function : ' . __FUNCTION__);
    }

    /*
    Called when Paypal Express option is used. Here we create the order using the fields that
    would normally be filled out by the customer on the checkout page, but are skipped in the
    express payment flow.
    */
    private function paypal_create_order()
    {
        self::debug_log('Begin function : ' . __FUNCTION__);
        $chosen_shipping_methods = maybe_unserialize(WC()->session->chosen_shipping_methods);
        self::debug_log('$chosen_shipping_methods = ' . print_r($chosen_shipping_methods,true));

        $_POST['payment_method'] = $this->id;
        $_POST['shipping_method'] =  $chosen_shipping_methods;
        $_POST['ship_to_different_address'] = true; // Paypal does not send billing addresses, only shipping
        $this->set_session( 'chosen_shipping_methods',  maybe_unserialize(WC()->session->chosen_shipping_methods) );

        $result = maybe_unserialize(WC()->session->result);
        $hpsBuyerData = maybe_unserialize($result->buyer);
        $hpsShippingInfo = maybe_unserialize($result->shipping);
        $ship_name =  $unfiltered_name_parts = explode(" ",$hpsShippingInfo->name);

        $_POST['billing_first_name'] = isset($hpsBuyerData->firstName)    ? $hpsBuyerData->firstName    : '';
        $_POST['billing_last_name'] = isset($hpsBuyerData->lastName)     ? $hpsBuyerData->lastName     : '';
        // Paypal doesn't provide billing address so using shipping address
        $_POST['billing_address_1'] = isset($hpsShippingInfo->address->address) ? $hpsShippingInfo->address->address : '';
        $_POST['billing_address_2'] = isset($hpsShippingInfo->address->address_1) ? $hpsShippingInfo->address->address_1 : 'NA';
        $_POST['billing_city'] = isset($hpsShippingInfo->address->city)    ? $hpsShippingInfo->address->city : '';
        $_POST['billing_state'] = isset($hpsShippingInfo->address->state)   ? $hpsShippingInfo->address->state : '';
        $_POST['billing_postcode'] = isset($hpsShippingInfo->address->zip)     ? $hpsShippingInfo->address->zip : '';
        //
        $_POST['billing_country'] = isset($hpsBuyerData->countryCode)  ? $hpsBuyerData->countryCode  : '';
        $_POST['billing_email'] = isset($hpsBuyerData->emailAddress) ? $hpsBuyerData->emailAddress : '';
        $_POST['billing_phone'] = isset($hpsBuyerData->phone) ? $hpsBuyerData->phone : '5555555555';

        $_POST['shipping_first_name'] = isset($ship_name[0]) ? $ship_name[0] : '';
        $_POST['shipping_last_name'] = isset($ship_name[1]) ? $ship_name[1] : '';
        $_POST['shipping_address_1'] = isset($hpsShippingInfo->address->address) ? $hpsShippingInfo->address->address : '';
        $_POST['shipping_address_2'] = isset($hpsShippingInfo->address->address_1) ? $hpsShippingInfo->address->address_1 : 'NA';
        $_POST['shipping_city'] = isset($hpsShippingInfo->address->city)    ? $hpsShippingInfo->address->city : '';
        $_POST['shipping_state'] = isset($hpsShippingInfo->address->state)   ? $hpsShippingInfo->address->state : '';
        $_POST['shipping_postcode'] = isset($hpsShippingInfo->address->zip)     ? $hpsShippingInfo->address->zip : '';
        $_POST['shipping_country'] = isset($hpsShippingInfo->address->country) ? $hpsShippingInfo->address->country : '';

        // If the user is logged in then use his woocommerce profile billing address information if entered
        if(is_user_logged_in()) {

            $_POST['billing_address_1'] = isset(WC()->customer->address_1) && !empty(WC()->customer->address_1) ? WC()->customer->address_1 : $_POST['billing_address_1'];
            $_POST['billing_address_2'] = isset(WC()->customer->address_2) && !empty(WC()->customer->address_2) ? WC()->customer->address_2 : $_POST['billing_address_2'];
            $_POST['billing_city']      = isset(WC()->customer->city)      && !empty(WC()->customer->city)      ? WC()->customer->city      : $_POST['billing_city'];
            $_POST['billing_state']     = isset(WC()->customer->state)     && !empty(WC()->customer->state)     ? WC()->customer->state     : $_POST['billing_state'];
            $_POST['billing_postcode']  = isset(WC()->customer->postcode)  && !empty(WC()->customer->postcode)  ? WC()->customer->postcode  : $_POST['billing_postcode'];
        }

        $wpnonce = wp_create_nonce('woocommerce-process_checkout');
        $_POST['_wpnonce'] = $wpnonce;
        self::debug_log('paypal_create_order() : $_POST = ' . print_r($_POST,true));
        $this->set_session('ppexpress_checkout_form', serialize($_POST));
        $all_notices = WC()->session->get('wc_notices', array());
        if(sizeof($all_notices)>0) {
            self::debug_log('$all_notices = ' . print_r($all_notices, true));
        }
        $this->set_session('process_payment_override',1);
        WC()->checkout->process_checkout();
        self::debug_log('End function : ' . __FUNCTION__);
        return;

        /*
        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

        */
    }
    private function paypal_finalize_order()
    {
        self::debug_log('Begin function : ' . __FUNCTION__);
        //set token from session if available
        $token = $this->get_session('TOKEN');
        if ( is_null($token) && isset( $_GET['token'] ))
        {
            $token = $_GET['token'];
        }

        $porticoSessionInfo = unserialize( $this->get_session('RESULT') );
        $order_id = WC()->session->get('ss_order_id');
        if(!isset($order_id)) {
            self::debug_log('No order id found, creating order');
            $this->paypal_create_order();
            $order_id = WC()->session->order_awaiting_payment;
        }
        self::debug_log('paypal_finalize_order : $order_id = ' . $order_id);

        $order = wc_get_order( $order_id );

        if(!isset($order) || $order == false)
        {
            wc_add_notice('Order information was not found, unable to create order', 'error');
            wp_redirect(wc_get_cart_url());
            exit();
        }

        // cleanup paypal express dummy values
        $billingAddress = $order->get_address();
        $shippingAddress = $order->get_address('shipping');
        self::debug_log('$billingAddress = ' . print_r($billingAddress,true));
        self::debug_log('$shippingAddress = ' . print_r($shippingAddress,true));
        $billingAddress['address_2'] = ($billingAddress['address_2']=='NA') ? '' : $billingAddress['address_2'];
        $shippingAddress['address_2'] = $shippingAddress['address_2']=='NA' ? '' : $shippingAddress['address_2'];
        $billingAddress['phone'] = $billingAddress['phone'] == '5555555555' ? '' : $billingAddress['phone'];
        $order->set_address($billingAddress);
        $order->set_address($shippingAddress,'shipping');

        self::debug_log('paypal_finalize_order() : $order = ' . print_r($order,true));

        $porticoService = $this->getPorticoService();
        $checkoutForm = $this->get_session('checkout_form');

        $payment = $porticoSessionInfo->payment;
        $orderTotal = $payment->subtotal + $payment->shippingAmount + $payment->taxAmount;
        $currency = strtolower(get_woocommerce_currency());
        //call portico with sale
        $response = null;
        try
        { 
            if($this->paymentaction == 'sale')
            {
                $response = $porticoService->sale(
                            $token,
                            $orderTotal,
                            $currency,
                            $porticoSessionInfo->buyer,
                            $porticoSessionInfo->payment,
                            $porticoSessionInfo->shipping,
                            $porticoSessionInfo->lineItems);
            }
            else
            {
                $response = $porticoService->authorize(
                            $token,
                            $orderTotal,
                            $currency,
                            $porticoSessionInfo->buyer,
                            $porticoSessionInfo->payment,
                            $porticoSessionInfo->shipping,
                            $porticoSessionInfo->lineItems);
            }
        }
        catch (Exception $e) 
        {
            $error = __('Error finalizing PayPal Portico transaction:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error, $notice_type='error');
            }          
            self::debug_log($error);
            return false;
        }

        self::debug_log('$response->responseCode = ' . $response->responseCode);
        if($response->responseCode == '0')
        {

            $order->add_order_note( __( 'Heartland PayPal payment completed. Transaction id: ' . $response->transactionId, 'wc_securesubmit' ));
            $order->payment_complete($response->transactionId);

            $this->set_session('ss_order_id', null);
            $this->set_session('ss_express_checkout_initiated',null);
            $this->set_session('checkout_form', null);
            $this->set_session('ss-paypal-express-checkout-inprogress',null);

            // Empty the Cart
            WC()->cart->empty_cart();

            self::debug_log('Redirecting to ' . $this->get_return_url( $order ));
            self::debug_log('End function : ' . __FUNCTION__);
            wp_redirect( $this->get_return_url( $order ));
            exit();
        }
        self::debug_log('Error Section of  : ' . __FUNCTION__);
        wc_add_notice(  sprintf( __('There was a problem paying with PayPal.  Please try another method.', 'wc_securesubmit' ) ), 'error' );
        self::debug_log('Order did not complete successfully. Order ID: ' . $order_id . '. Return Status Code: ' . $response->responseCode);
        wp_redirect(get_permalink(wc_get_page_id('cart')));
        self::debug_log('End function : ' . __FUNCTION__ );
        exit();
    }

    private function set_session( $key, $value ) 
    {
        self::debug_log('['.debug_backtrace()[1]['function'].'] Saved to session : ' . print_r($key,true) . ' = ' . print_r($value,true));
        WC()->session->$key = $value;
    }

    private function get_session( $key ) 
    {
        self::debug_log('['.debug_backtrace()[1]['function'].'] Requested session value : ' . print_r($key,true) );
        return WC()->session->$key;
    }
       
    public function get_state_code( $country, $state ) {
        // If not US address, then convert state to abbreviation
        if ( $country != 'US' ) {
            $local_states = WC()->countries->states[ WC()->customer->get_country() ];
            if ( ! empty( $local_states ) && in_array($state, $local_states)) {
                foreach ( $local_states as $key => $val ) {
                    if ( $val == $state) {
                        $state = $key;
                    }
                }
            }
        }
        return $state;
    }

    /**
     * @return false|string
     */
    public function get_review_order_page($create_if_not_found = true)
    {
        $review_order_page_url = get_permalink(wc_get_page_id('review_order'));
        if (!$review_order_page_url && $create_if_not_found) {
            include_once(WC()->plugin_path() . '/includes/admin/wc-admin-functions.php');
            $page_id = wc_create_page(esc_sql(_x('review-order', 'page_slug', 'woocommerce')), 'woocommerce_review_order_page_id', __('Checkout &rarr; Review Order', 'wc_securesubmit'), '[woocommerce_review_order]', wc_get_page_id('checkout'));
            $review_order_page_url = get_permalink($page_id);
            return $review_order_page_url;
        }
        return $review_order_page_url;
    }

    /**
     * @param $order
     * @return HpsBuyerData
     */
    public function get_buyer_data($order)
    {
        $buyer = new HpsBuyerData();
        $buyer->emailAddress = $order->billing_email;
        $buyer->cancelUrl = wc_get_cart_url();;
        $review_order_page_url = $this->get_review_order_page();
        $buyer->returnUrl = add_query_arg('pp_action', 'revieworder', $review_order_page_url);
        return $buyer;
    }

    /**
     * @param $order
     * @return HpsPaymentData
     */
    public function get_payment_data($order)
    {
        $payment = new HpsPaymentData();
        $payment->subtotal = $order->get_subtotal();
        $payment->shippingAmount = $order->get_total_shipping();
        $payment->taxAmount = $order->get_total_tax();
        $payment->paymentType = $this->paymentaction == 'authorization' ? 'Authorization' : 'Sale';
        return $payment;
    }

    /**
     * @param $cart
     * @param $hpsLineItems
     * @return array
     */
    public function get_cart_lineitems()
    {
        $hpsLineItems = array();
        $cart = WC()->cart;
        $cartItems = $cart->get_cart();
        foreach ($cartItems as $cartItem) {
            $lineItem = new HpsLineItem();
            $lineItem->name = $cartItem['data']->post->post_name;                            // hpsLineItem : name
            $lineItem->description = substr($cartItem['data']->post->post_excerpt,0,127);    // hpsLineItem : description
            $lineItem->number = $cartItem['product_id'];                                     // hpsLineItem : number
            $lineItem->amount = $cartItem['line_total'] / $cartItem['quantity'];             // hpsLineItem : price
            $lineItem->quantity = $cartItem['quantity'];                                     // hpsLineItem : quantity
            $hpsLineItems[] = $lineItem;
        }

        if(isset($cart->discount_cart) && $cart->discount_cart>0)
        {
            $discountItem = new $hpsLineItems;
            $discountItem->name = 'Discount';
            $discountItem->number = 'discount';
            $discountItem->amount = $cart->discount_cart;
            $hpsLineItems[] = $discountItem;
        }

        return $hpsLineItems;
    }

    /**
     * @param $cart
     * @return HpsPaymentData
     */
    public function get_cart_paymentdata()
    {
        $cart = WC()->cart;
        $payment = new HpsPaymentData();
        $payment->subtotal = $cart->subtotal;
        $payment->shippingAmount = $cart->shipping_total;
        $payment->taxAmount = $cart->tax_total;
        $payment->paymentType = $this->paymentaction == 'authorization' ? 'Authorization' : 'Sale';
        return $payment;
    }

}
