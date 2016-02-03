<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_Gateway_SecureSubmit_MasterPass_ReviewOrder
{
    protected $masterpass = null;
    protected $transactionsAllowed  = null;

    public function __construct(&$masterpass = null)
    {
      $this->masterpass = $masterpass;
    }

    /**
     * Callback for `woocommerce_review_order` shortcode. Called from
     * `WC_Shortcodes::shortcode_wrapper`.
     */
    public function call()
    {
        $action = (isset($_POST['mp_action']) ? $_POST['mp_action'] : (isset($_GET['mp_action']) ? $_GET['mp_action'] : null));
        if ($action && 'process_payment' === $action) {
            $this->createOrderAndProcessPayment();
        } else if ($action && 'review_order' === $action) {
            $this->authenticateAndDisplayReviewOrder();
        }
    }

    /**
     * Authenticates a return request from MasterPass
     *
     * On success, data is cached in session storage to properly handle consumer
     * browser refreshes, and the consumer is presented with the order review
     * page. On cancel or failure, consumer is redirected back to the checkout
     * page.
     */
    public function authenticateAndDisplayReviewOrder()
    {
        $status = isset($_GET['mpstatus']) ? $_GET['mpstatus'] : '';

        if ('success' !== $status) {
            $error = __('Error:', 'wc_securesubmit') . ' Invalid MasterPass session';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                WC()->add_error($error);
            }
            // headers already sent at this point. redirect with js
            echo '<script type="text/javascript">window.location.href = \'' . WC()->cart->get_checkout_url() . '\';</script>';
            wp_die();
        }

        $payload = get_transient('_masterpass_payload');
        $orderId = get_transient('_masterpass_order_id');
        $orderNumber = get_transient('_masterpass_order_number');

        $checkoutResourceUrl = isset($_GET['checkout_resource_url']) ? urldecode($_GET['checkout_resource_url']) : '';
        $oauthVerifier = isset($_GET['oauth_verifier']) ? $_GET['oauth_verifier'] : '';
        $oauthToken = isset($_GET['oauth_token']) ? $_GET['oauth_token'] : '';
        $pairingVerifier = isset($_GET['pairing_verifier']) ? $_GET['pairing_verifier'] : '';
        $pairingToken = isset($_GET['pairing_token']) ? $_GET['pairing_token'] : '';
        $data = null;

        try {
            $service = $this->masterpass->getService();
            $orderData = new HpsOrderData();
            $orderData->transactionStatus = $status;
            if ($pairingToken !== '' && $pairingVerifier !== '') {
                $orderData->pairingToken = $pairingToken;
                $orderData->pairingVerifier = $pairingVerifier;
                $orderData->checkoutType = HpsCentinelCheckoutType::PAIRING_CHECKOUT;
            }

            // Authenticate the request with the information we've gathered
            $response = $service->authenticate(
                $orderId,
                $oauthToken,
                $oauthVerifier,
                $payload,
                $checkoutResourceUrl,
                $orderData
            );

            if ('0' !== $response->errorNumber) {
                throw new Exception();
            }

            $data = (object)array_merge((array)$response, array(
                'status' => $status,
            ));
            WC()->session->set('masterpass_authenticate', $data);
        } catch (Exception $e) {
            $data = WC()->session->get('masterpass_authenticate');
        }

        $pluginPath = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/';
        wc_get_template('masterpass-review-order.php', array('payload' => $data, 'masterpass' => $this->masterpass), '', $pluginPath);
    }

    /**
     * Creates the order object and authorization against MasterPass data
     *
     * On success, consumer is redirected to order received page. On failure,
     * consumer is redirected to cart page.
     */
    public function createOrderAndProcessPayment()
    {
        $destination = '';
        $this->transactionQuery('start');

        try {
            // default order args, note that status is checked for validity in wc_create_order()
            $orderData = array();
            $checkoutForm = maybe_unserialize(WC()->session->checkout_form);

            // if creating order for existing customer
            $currentUser = wp_get_current_user();
            if (false !== $currentUser) {
                $orderData['customer_id'] = $currentUser->ID;
            }

            // create the pending order
            $order = wc_create_order($orderData);
            if (is_wp_error($order)) {
                throw new Exception(sprintf(__('Cannot create order: %s', 'wc_securesubmit'), implode(', ', $order->get_error_messages())));
            }

            // billing/shipping addresses
            $this->setOrderAddresses($order, $checkoutForm);

            // order information
      			$this->setOrderInformation($order);

            // calculate totals and set them
            $order->calculate_totals();

            // process payment
            WC()->session->order_awaiting_payment = $order->id;
            $payment = $this->masterpass->process_payment($order->id);

            if ('success' !== $payment['result']) {
                throw new Exception(__('Payment was unsuccessful', 'wc_securesubmit'));
            }

            // set additional payment method information on order
            update_post_meta($order->id, '_payment_method', $this->masterpass->id);
            update_post_meta($order->id, '_payment_method_title', $this->masterpass->title);
            update_post_meta($order->id, '_order_currency', get_woocommerce_currency());

            // clean up
          	wc_delete_shop_order_transients($order->id);
            $this->transactionQuery('commit');
            $destination = $order->get_checkout_order_received_url();
        } catch (Exception $e) {
            $this->transactionQuery('rollback');
            $destination = WC()->cart->get_cart_url();
        }

        // headers already sent at this point. redirect with js
        echo '<script type="text/javascript">window.location.href = \'' . $destination . '\';</script>';
        wp_die();
    }

    /**
     * Adds cart information to order
     */
    protected function setOrderInformation($order)
    {
        $cart = WC()->cart;

        // Store the line items to the new/resumed order
        foreach ($cart->get_cart() as $cartItemKey => $values) {
          $itemId = $order->add_product(
            $values['data'],
            $values['quantity'],
            array(
              'variation' => $values['variation'],
              'totals'    => array(
                'subtotal'     => $values['line_subtotal'],
                'subtotal_tax' => $values['line_subtotal_tax'],
                'total'        => $values['line_total'],
                'tax'          => $values['line_tax'],
                'tax_data'     => $values['line_tax_data'] // Since 2.2
              )
            )
          );
          if (!$itemId) {
            throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'woocommerce'), 525));
          }
          // Allow plugins to add order item meta
          do_action('woocommerce_add_order_item_meta', $itemId, $values, $cartItemKey);
        }

        // Store fees
        foreach ($cart->get_fees() as $feeKey => $fee) {
          $itemId = $order->add_fee($fee);
          if (!$itemId) {
            throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'woocommerce'), 526));
          }
          // Allow plugins to add order item meta to fees
          do_action('woocommerce_add_order_fee_meta', $order->d, $itemId, $fee, $feeKey);
        }

        // Store shipping for all packages
        foreach (WC()->shipping->get_packages() as $packageKey => $package) {
          if (isset($package['rates'][$this->shipping_methods[$packageKey]])) {
            $itemId = $order->add_shipping($package['rates'][$this->shipping_methods[$packageKey]]);
            if (!$itemId) {
              throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'woocommerce'), 527));
            }
            // Allows plugins to add order item meta to shipping
            do_action('woocommerce_add_shipping_order_item', $orderId, $itemId, $packageKey);
          }
        }

        // Store tax rows
        foreach (array_keys($cart->taxes + $cart->shipping_taxes) as $taxRateId) {
          $itemId = $order->add_tax(
              $taxRateId,
              $cart->get_tax_amount($taxRateId),
              $cart->get_shipping_tax_amount($taxRateId)
          );
          if ($taxRateId && !$itemId && apply_filters('woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated') !== $taxRateId) {
            throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'woocommerce'), 528));
          }
        }

        // Store coupons
        foreach ($cart->get_coupons() as $code => $coupon) {
          $itemId = $order->add_coupon(
              $code,
              $cart->get_coupon_discount_amount($code),
              $cart->get_coupon_discount_tax_amount($code)
          );
          if (!$itemId) {
            throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'woocommerce'), 529));
          }
        }
    }

    /**
     * Processes and sets address information on an order
     *
     * @param WC_Order $order
     * @param array    $checkoutForm
     */
    protected function setOrderAddresses($order, $checkoutForm)
    {
        $addressFields = array(
            'first_name',
            'last_name',
            'company',
            'email',
            'phone',
            'address_1',
            'address_2',
            'city',
            'state',
            'postcode',
            'country',
        );

        // get addresses
        $billing = $this->masterpass->getWCAddress($this->masterpass->getBuyerData($checkoutForm));
        $shipping = $this->masterpass->getWCAddress($this->masterpass->getShippingInfo($checkoutForm));

        // get cleaner data
        foreach ($addressFields as $field) {
            if (isset($billing[$field])) {
                $billing[$field] = wc_clean($billing[$field]);
            }
        }
        unset($addressFields['email']);
        unset($addressFields['phone']);

        foreach ($addressFields as $field) {
            if (isset($billing[$field])) {
                $billing[$field] = wc_clean($billing[$field]);
            }
        }

        // set addresses on order
        $order->set_address($billing, 'billing');
        $order->set_address($shipping, 'shipping');

        // set addresses on user if exists on order
        if ($order->get_user_id()) {
            foreach ($billing as $key => $value) {
                update_user_meta($order->get_user_id(), 'billing_' . $key, $value);
            }
            foreach ($shipping as $key => $value) {
                update_user_meta($order->get_user_id(), 'shipping_' . $key, $value);
            }
        }
    }

    /**
     * Reimplementation of `wc_transaction_query` to support transactions in
     * versions of WooCommerce
     *
     * @param string $type
     */
    protected function transactionQuery($type)
    {
        global $wpdb;
        if (null === $this->transactionsAllowed) {
            $this->transactionsAllowed = false;
            $wpdb->hide_errors();
            $result = $wpdb->query('SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;');
            if (false !== $result) {
                $this->transactionsAllowed = true;
            }
        }

        if ($this->transactionsAllowed) {
            switch ($type) {
                case 'commit':
                    $wpdb->query('COMMIT');
                    break;
                case 'rollback':
                    $wpdb->query('ROLLBACK');
                    break;
                default:
                    $wpdb->query('START TRANSACTION');
                    break;
            }
        }
    }
}
