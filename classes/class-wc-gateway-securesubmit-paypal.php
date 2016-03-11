<?php
/*
Plugin Name: WooCommerce SecureSubmit Gateway
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WooCommerce.
Version: 1.1.1
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
        $this->order_button_text  = __( 'Proceed to PayPal', 'woocommerce' );
        $this->method_title       = __( 'Heartland Paypal', 'woocommerce' );
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
    public static function log( $message ) {
        if ( self::$log_enabled ) {
            if ( empty( self::$log ) ) {
                self::$log = new WC_Logger();
            }
            self::$log->add( 'paypal', $message );
        }
    }

    /**
     * get_icon function.
     *
     * @return string
     */
    public function get_icon() {
        $icon_html = '';
        $icon      = (array) $this->get_icon_image( WC()->countries->get_base_country() );

        foreach ( $icon as $i ) {
            $icon_html .= '<img src="' . esc_attr( $i ) . '" alt="' . __( 'PayPal Acceptance Mark', 'woocommerce' ) . '" />';
        }

        $icon_html .= sprintf( '<a href="%1$s" class="about_paypal" onclick="javascript:window.open(\'%1$s\',\'WIPaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is PayPal?', 'woocommerce' ) . '">' . esc_attr__( 'What is PayPal?', 'woocommerce' ) . '</a>', esc_url( $this->get_icon_url( WC()->countries->get_base_country() ) ) );

        return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    }

    /**
     * Get the link for an icon based on country
     * @param  string $country
     * @return string
     */
    private function get_icon_url( $country ) {

        $link = 'https://www.paypal.com/' . strtolower( $country ) . '/webapps/mpp/paypal-popup';
        return $link;
    }

    /**
     * Get PayPal images for a country
     * @param  string $country
     * @return array of image URLs
     */
    private function get_icon_image( $country ) {
        $icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
        return apply_filters( 'woocommerce_paypal_icon', $icon );
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
        error_log(__FUNCTION__);
        global $woocommerce, $post;

        $hpsBuyer = new HpsBuyerData();
        $review_order_page_url = add_query_arg( 'pp_action','revieworder', $this->get_review_order_page() );
        $hpsBuyer->returnUrl = $review_order_page_url;
        $hpsBuyer->cancelUrl = wc_get_cart_url();
        $hpsLineItems = $this->get_cart_lineitems();
        $payment = $this->get_cart_paymentdata();
        $porticoService = $this->getPorticoService();

        try
        {
            $response = $porticoService->createSession(WC()->cart->cart_contents_total,"USD", $hpsBuyer, $payment, null, $hpsLineItems);
        }
        catch (Exception $e)
        {
            //echo print_r($e,true);
            $error = __('Error creating PayPal Portico session:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            return false;
        }


        $redirectUrl = $response->redirectUrl;
        echo('<script type="application/javascript">window.location.href="'.$redirectUrl.'";</script>');
        exit();
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        error_log(__FUNCTION__);
    
        global $woocommerce, $post;
        $this->set_session('checkout_form', $_POST);
        $order = wc_get_order( $order_id );
         
        $porticoService = $this->getPorticoService();

        $shippingInfo = $this->getShippingInfo($order);
        $buyer = $this->get_buyer_data($order);
        $payment = $this->get_payment_data($order);
        $lineItems = $this->getLineItems($order);
        
        $orderTotal = $order->order_total;
        $currency = strtolower(get_woocommerce_currency());      
                

        //call portico to create session
        $response;
        try 
        {
            $response = $porticoService->createSession($orderTotal, $currency, $buyer, $payment, $shippingInfo, $lineItems);
        } 
        catch (Exception $e) 
        {
            $error = __('Error creating PayPal Portico session:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            return array(
                'result'   => 'fail',
                'redirect' => '' );
        }
        $redirectUrl = $response->redirectUrl;

        return array(
            'result'   => 'success',           
            'redirect' => $redirectUrl
        );
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
            $this->log( 'Refund Failed: No transaction ID' );
            return false;
        }
        $response = null;
        try {
            $porticoService = $this->getPorticoService();
            $isPartial = isset($amount) && $amount > 0;
            $response = $porticoService->refund($order->get_transaction_id(), $isPartial, $amount);

        } catch (Exception $e) {
            error_log('Exception-al doughnuts = ' . print_r($e,true));
            $error = __('Error processing refund:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"';
            error_log('$error = ' . $error);
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            return false;
        }

        error_log('Refund $response = ' . print_r($response,true));
        if ($response->responseCode=="00") {
            $reason = $reason == '' ? '' : '. Reason for refund: '.$reason;
            $order->add_order_note( __( 'SecureSubmit PayPal refund completed. Transaction id: ' . $response->transactionId . $reason, 'paypal-for-woocommerce' ));
            return true;
        }

        $this->log( 'Refund Failed in Portico call with responseCode ' . $response->responseCode);
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
        
        //$this->log('shipname: ' . $order->shipping_first_name . ' ' . $order->shipping_last_name);
        //$this->log('shipping_address_1: ' . $order->shipping_address_1);
        //$this->log('shipping_city: ' . $order->shipping_address_1);
        //$this->log('shipping_state: ' . $order->shipping_address_1);
        //$this->log('shipping_zip: ' . preg_replace('/[^a-zA-Z0-9]/', '', $order->shipping_postcode));
        //$this->log('shipping_country: ' . $order->shipping_countr);
        
        return $shippingInfo;
    }
    
    protected function getLineItems($order)
    {
        $lineItems = array();
        $calculated_total = 0;
        
        foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			if ( 'fee' === $item['type'] ) {
				$lineItem        = $this->createLineItem( $item['name'], 1, $item['line_total'] );
				$calculated_total += $item['line_total'];
			} else {
				$product          = $order->get_product_from_item( $item );
				$lineItem         = $this->createLineItem( $this->get_order_item_name( $order, $item ), $item['qty'], $order->get_item_subtotal( $item, false ), $product->get_sku(), $order->get_item_tax($item) );
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
        
        //$this->log('name: ' . $line_item->name . 
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
    function process_paypal_checkout($posted = null) 
    {
        error_log(__FUNCTION__);
        error_log('$_POST = ' . print_r($_GET,true));
        if ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'revieworder' )
		{
            $this->paypal_review_order();
        }
        elseif ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'payaction' )
        {
            $this->paypal_finalize_order();
        }
    }
    
    private function paypal_review_order()
    {
        wc_clear_notices();
        // The customer has logged into PayPal and approved order.
        // Retrieve the shipping details and present the order for completion.
        if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
            define( 'WOOCOMMERCE_CHECKOUT', true );
        if ( isset( $_GET['token'] ) ) 
        {
            $token = $_GET['token'];
            $this->set_session( 'TOKEN', $token );            
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
                    wc_add_notice(  sprintf( __('We do not sell in your country, please try again with another address.', 'paypal-for-woocommerce' ) ), 'error' );
                    wp_redirect( get_permalink( wc_get_page_id( 'cart' ) ) );
                    exit;
                };
                WC()->customer->set_shipping_country( $shippingInfo->address->country );
            }
        }
        else
        {
            $this->log( "...ERROR: GetShippingDetails returned empty result" );
        }
        
        if(isset($_POST['createaccount'])){
            if(empty($_POST['username'])){
                wc_add_notice(__('Username is required', 'paypal-for-woocommerce'), 'error');
            }elseif(username_exists( $_POST['username'] )){
                wc_add_notice(__('This username is already registered.', 'paypal-for-woocommerce'), 'error');
            }elseif(empty($_POST['email'])){
                wc_add_notice(__('Please provide a valid email address.', 'paypal-for-woocommerce'), 'error');
            }elseif(empty($_POST['password']) || empty($_POST['repassword'])){
                wc_add_notice(__('Password is required.', 'paypal-for-woocommerce'), 'error');
            }elseif($_POST['password'] != $_POST['repassword']){
                wc_add_notice(__('Passwords do not match.', 'paypal-for-woocommerce'), 'error');
            }elseif(get_user_by( 'email',  $_POST['email'])!=false){
                wc_add_notice(__('This email address is already registered.', 'paypal-for-woocommerce'), 'error');
            }else{
                $data  = array(
                    'user_login' => addslashes( $_POST['username'] ),
                    'user_email' => addslashes( $_POST['email'] ),
                    'user_pass' => addslashes( $_POST['password'] ),
                );
                $userID = wp_insert_user($data);
                if( !is_wp_error($userID) ) {
                    update_user_meta( $userID, 'billing_first_name',  $shippingInfo->buyer->firstName );
                    update_user_meta( $userID, 'billing_last_name',   $shippingInfo->buyer->lastName );
                    update_user_meta( $userID, 'billing_address_1',  $shippingInfo->address->address );
                    update_user_meta( $userID, 'billing_state',   $shippingInfo->address->state );
                    update_user_meta( $userID, 'billing_email',   $session->buyer->emailAddress );
                    /* USER SIGON */
                    $user_login     = esc_attr($_POST["username"]);
                    $user_password  = esc_attr($_POST["password"]);
                    $user_email     = esc_attr($_POST["email"]);
                    $creds = array(
                        'user_login' => $user_login,
                        'user_password' => $user_password,
                        'remember' => true,
                    );

                    $user = wp_signon( $creds, false );
                    if ( is_wp_error($user) )
                        wc_add_notice($user->get_error_message(), 'error');
                    else
                    {
                        wp_set_current_user($user->ID); //Here is where we update the global user variables
                        header("Refresh:0");
                        die();
                    }
                }

            }
        }
    }
    
    private function paypal_finalize_order()
    {
        error_log(__FUNCTION__);
        //set token from session if available
        $token = $this->get_session('TOKEN');
        if ( is_null($token) && isset( $_GET['token'] )) 
        {
            $token = $_GET['token'];                    
        }

        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
        error_log('$chosen_shipping_methods = ' . print_r($chosen_shipping_methods,true));

        $order_id = WC()->checkout()->create_order();
        $order = wc_get_order( $order_id );

        $porticoService = $this->getPorticoService();
        $checkoutForm = $this->get_session('checkout_form');
        $porticoSessionInfo = unserialize( $this->get_session('RESULT') );

                    
        $payment = $porticoSessionInfo->payment;
        $orderTotal = $payment->subtotal + $payment->shippingAmount + $payment->taxAmount;
        $currency = strtolower(get_woocommerce_currency());


        //update order total and billing/shipping address
        $order->set_total($orderTotal);
        $order->set_address( array(
				'first_name'    => $checkoutForm["billing_first_name"],
				'last_name'     => $checkoutForm["billing_last_name"],
				'company'       => $checkoutForm["billing_company"],
				'address_1'     => $checkoutForm["billing_address_1"],
				'address_2'     => $checkoutForm["billing_address_2"],
				'city'          => $checkoutForm["billing_city"],
				'state'         => $checkoutForm["billing_state"],
				'postcode'      => $checkoutForm["billing_postcode"],
				'country'       => $checkoutForm["billing_country"],
                'email'         => $checkoutForm["billing_email"],
                'phone'         => $checkoutForm["billing_phone"]));
                      
        $order->set_address( array(
                'first_name'    => $checkoutForm["shipping_first_name"],
                'last_name'     => $checkoutForm["shipping_last_name"],
                'company'       => $checkoutForm["shipping_company"],
                'address_1'     => $checkoutForm["shipping_address_1"],
                'address_2'     => $checkoutForm["shipping_address_2"],
                'city'          => $checkoutForm["shipping_city"],
                'state'         => $checkoutForm["shipping_state"],
                'postcode'      => $checkoutForm["shipping_postcode"],
                'country'       => $checkoutForm["shipping_country"]), 'shipping');
        
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
            $this->log($error);
            return false;
        }
        
        if($response->responseCode == '0')
        {
            $payment_method = get_post_meta($order->id, '_payment_method');
            if( !isset($payment_method) || empty($payment_method)  ) {
                update_post_meta($order_id, '_payment_method', $this->id);
                update_post_meta($order_id, '_payment_method_title', $this->title);
            }

            $order->add_order_note( __( 'SecureSubmit PayPal payment completed. Transaction id: ' . $response->transactionId, 'paypal-for-woocommerce' ));
            $order->payment_complete($response->transactionId);
                    
            //add hook
            do_action( 'woocommerce_checkout_order_processed', $order_id );

            // Empty the Cart
            WC()->cart->empty_cart();

            wp_redirect( $this->get_return_url( $order ) );
            exit();
        }
        
        wc_add_notice(  sprintf( __('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce' ) ), 'error' );
        $this->log('Order did not complete successfully. Order ID: ' . $order_id . '. Return Status Code: ' . $response->responseCode);
        wp_redirect(get_permalink(wc_get_page_id('cart')));
        exit();
    }

    private function set_session( $key, $value ) 
    {
        WC()->session->$key = $value;
    }

    private function get_session( $key ) 
    {
        return WC()->session->$key;
    }
       
    function get_state_code( $country, $state ) {
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
            $page_id = wc_create_page(esc_sql(_x('review-order', 'page_slug', 'woocommerce')), 'woocommerce_review_order_page_id', __('Checkout &rarr; Review Order', 'paypal-for-woocommerce'), '[woocommerce_review_order]', wc_get_page_id('checkout'));
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
            $lineItem->name = $cartItem['data']->post->post_name;              // hpsLineItem : name
            $lineItem->description = $cartItem['data']->post->post_excerpt;    // hpsLineItem : desription
            $lineItem->number = $cartItem['product_id'];                       // hpsLineItem : number
            $lineItem->amount = $cartItem['line_total'] / $cartItem['quantity']; // hpsLineItem : price
            $lineItem->quantity = $cartItem['quantity'];                       // hpsLineItem : quantity
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
