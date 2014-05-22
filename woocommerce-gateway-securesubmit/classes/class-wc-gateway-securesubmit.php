<?php
/**
 * WC_Gateway_SecureSubmit class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_SecureSubmit extends WC_Payment_Gateway {
	function __construct() {
		require_once 'includes/Hps.php';
		
		$this->id					= 'securesubmit';
		$this->method_title 		= __('SecureSubmit', 'wc_securesubmit');
		$this->icon 				= plugins_url('/assets/images/cards.png', dirname( __FILE__));
		$this->has_fields 			= true;
		$this->init_form_fields();
		$this->init_settings();
		$this->title 				= $this->settings['title'];
		$this->description 			= $this->settings['description'];
		$this->enabled 				= $this->settings['enabled'];
		$this->secret_key 			= $this->settings['secret_key'];
		$this->public_key			= $this->settings['public_key'];
		$this->paymentaction		= $this->settings['paymentaction'];

		add_action('wp_enqueue_scripts', array( &$this, 'payment_scripts' ) );
		add_action('admin_notices', array( &$this, 'checks' ) );
		add_action('woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options'));
	}

	function checks() {
     	global $woocommerce;

     	if ($this->enabled == 'no')
     		return;

     	if (!$this->secret_key) {
	     	echo '<div class="error"><p>' . sprintf(__('SecureSubmit error: Please enter your secret key <a href="%s">here</a>', 'wc_securesubmit'), admin_url('admin.php?page=woocommerce&tab=payment_gateways&subtab=gateway-securesubmit')) . '</p></div>';
	     	return;
     	} elseif (!$this->public_key) {
     		echo '<div class="error"><p>' . sprintf(__('SecureSubmit error: Please enter your public key <a href="%s">here</a>', 'wc_securesubmit'), admin_url('admin.php?page=woocommerce&tab=payment_gateways&subtab=gateway-securesubmit')) . '</p></div>';
     		return;
     	}
	}

	function is_available() {
		global $woocommerce;

		if ($this->enabled == "yes") {
			if ($woocommerce->version < '1.5.8')
				return false;

            // we will be adding more currencies in the near future, but today we are bound to USD
			if (!in_array(get_option('woocommerce_currency'), array( 'USD')))
				return false;

			if (!$this->secret_key) 
                return false;
                
			if (!$this->public_key) 
                return false;

			return true;
		}

		return false;
	}

    function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __('Enable/Disable', 'wc_securesubmit'),
							'label' => __('Enable SecureSubmit', 'wc_securesubmit'),
							'type' => 'checkbox',
							'description' => '',
							'default' => 'no'
						),
			'title' => array(
							'title' => __('Title', 'wc_securesubmit'),
							'type' => 'text',
							'description' => __('This controls the title which the user sees during checkout.', 'wc_securesubmit'),
							'default' => __( 'Credit card (SecureSubmit)', 'wc_securesubmit' )
						),
			'description' => array(
							'title' => __('Description', 'wc_securesubmit' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'wc_securesubmit'),
							'default' => 'Pay with your credit card via SecureSubmit.'
						),
			'secret_key' => array(
							'title' => __('Secret Key', 'wc_securesubmit' ),
							'type' => 'text',
							'description' => __('Get your API keys from your SecureSubmit account.', 'wc_securesubmit'),
							'default' => ''
						),
			'public_key' => array(
							'title' => __('Public Key', 'wc_securesubmit'),
							'type' => 'text',
							'description' => __('Get your API keys from your SecureSubmit account.', 'wc_securesubmit'),
							'default' => ''
						),
			'paymentaction' => array(
					'title'       => __( 'Payment Action', 'wc_securesubmit' ),
					'type'        => 'select',
					'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'wc_securesubmit' ),
					'default'     => 'sale',
					'desc_tip'    => true,
					'options'     => array(
							'sale'          => __( 'Capture', 'wc_securesubmit' ),
							'authorization' => __( 'Authorize', 'wc_securesubmit' )
					)
			)
			);
    }

	function admin_options() {
    	?>
    	<h3><?php _e('SecureSubmit', 'wc_securesubmit'); ?></h3>
    	<p><?php _e('SecureSubmit submits the credit card data directly to Heartland Payment Systems which responds with a token. That token is later charged.', 'wc_securesubmit'); ?></p>
    	<?php
		if (in_array(get_option('woocommerce_currency'), array('USD'))) {
    		?>
    		<table class="form-table">
	    		<?php $this->generate_settings_html(); ?>
			</table>
    		<?php
		} else {
			?>
			<div class="inline error"><p><strong><?php _e('Gateway Disabled', 'wc_securesubmit'); ?></strong> <?php echo __('Choose US Dollars as your store currency to enable SecureSubmit.', 'wc_securesubmit'); ?></p></div>
		<?php
		}
    }

	function payment_fields() {
		global $woocommerce;
		?>
		<fieldset>
			<?php if ( $this->description ) : ?>
				<p><?php echo $this->description; ?>
			<?php endif; ?>

			<div class="securesubmit_new_card">

				<p class="form-row form-row-wide">
					<label for="securesubmit_card_number"><?php _e("Credit Card number", 'wc_securesubmit') ?> <span class="required">*</span></label>
					<input type="text" autocomplete="off" class="input-text card-number" />
				</p>
				<div class="clear"></div>
				<p class="form-row form-row-first">
					<label for="cc-expire-month"><?php _e("Expiration date", 'wc_securesubmit') ?> <span class="required">*</span></label>
					<select id="cc-expire-month" class="woocommerce-select woocommerce-cc-month card-expiry-month">
						<option value=""><?php _e('Month', 'wc_securesubmit') ?></option>
						<?php
							$months = array();
							for ($i = 1; $i <= 12; $i++) :
								$timestamp = mktime(0, 0, 0, $i, 1);
								$months[date('n', $timestamp)] = date('F', $timestamp);
							endfor;
							foreach ($months as $num => $name) printf('<option value="%u">%s</option>', $num, $name);
						?>
					</select>
					<select id="cc-expire-year" class="woocommerce-select woocommerce-cc-year card-expiry-year">
						<option value=""><?php _e('Year', 'wc_securesubmit') ?></option>
						<?php
							for ($i = date('y'); $i <= date('y') + 15; $i++) printf('<option value="20%u">20%u</option>', $i, $i);
						?>
					</select>
				</p>
				<p class="form-row form-row-last">
					<label for="securesubmit_card_csc"><?php _e("Card security code", 'wc_securesubmit') ?> <span class="required">*</span></label>
					<input type="text" id="securesubmit_card_csc" maxlength="4" style="width:4em;" autocomplete="off" class="input-text card-cvc" />
					<span class="help securesubmit_card_csc_description"></span>
				</p>
				<div class="clear"></div>
			</div>
		</fieldset>
		<?php
	}

	function payment_scripts() {
		if (!is_checkout())
			return;
			
		// SecureSubmit tokenization library
		wp_enqueue_script( 'woocommerce_lib', plugins_url( 'assets/js/secure.submit-1.0.2.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.0', true );
		// SecureSubmit js controller for WooCommerce
		wp_enqueue_script( 'woocommerce_securesubmit', plugins_url( 'assets/js/securesubmit.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.0', true );

		$securesubmit_params = array(
			'key' => $this->public_key
		);

		if (is_page(woocommerce_get_page_id('pay'))) {
			$order_key = urldecode($_GET['order']);
			$order_id = (int) $_GET['order_id'];
			$order = new WC_Order($order_id);
		}

		wp_localize_script('woocommerce_securesubmit', 'wc_securesubmit_params', $securesubmit_params);
	}

	function process_payment($order_id) {
		global $woocommerce;

		$order = new WC_Order($order_id);
		$securesubmit_token = isset($_POST['securesubmit_token']) ? woocommerce_clean($_POST['securesubmit_token']) : '';

		try {
			$post_data = array();

			if (empty( $securesubmit_token ))
				throw new Exception(__( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'wc_securesubmit'));

            $config = new HpsConfiguration();
            $config->secretApiKey = $this->secret_key;
            $config->versionNumber = '1510';
            $config->developerId = '002914';
			
			$chargeService = new HpsChargeService($config);
            
            $hpsaddress = new HpsAddress();
            $hpsaddress->address = $order->billing_address_1;
            $hpsaddress->city = $order->billing_city;
            $hpsaddress->state = $order->billing_state;
            $hpsaddress->zip = preg_replace('/[^0-9]/', '', $order->billing_postcode);
            $hpsaddress->country = $order->billing_country;

            $cardHolder = new HpsCardHolder();
            $cardHolder->firstName = $order->billing_first_name;
            $cardHolder->lastName = $order->billing_last_name;
            $cardHolder->phone = preg_replace('/[^0-9]/', '', $order->billing_phone);
            $cardHolder->emailAddress = $order->billing_email;
            $cardHolder->address = $hpsaddress;

            $hpstoken = new HpsTokenData();
            $hpstoken->tokenValue = $securesubmit_token;
			
            try	{
                if ($this->paymentaction == 'sale')
                {
                    $response = $chargeService->charge(
                        $order->order_total,
                        strtolower(get_woocommerce_currency()),
                        $hpstoken,
                        $cardHolder,
                        false,
                        null);
                } else {
                    $response = $chargeService->authorize(
                        $order->order_total,
                        strtolower(get_woocommerce_currency()),
                        $hpstoken,
                        $cardHolder,
                        false,
                        null);
                }
                
                $order->add_order_note(__('SecureSubmit payment completed', 'hps-securesubmit') . ' (Transaction ID: ' . $response->transactionId . ')');
                $order->payment_complete();
                $woocommerce->cart->empty_cart();
                
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                );
            } catch(HpsException $e) {
                throw new Exception(__($e->getMessage(), 'wc_securesubmit'));
            }                
		} catch( Exception $e ) {
			$woocommerce->add_error(__('Error:', 'wc_securesubmit') . ' "' . $e->getMessage() . '"');
			return;
		}
	}
}