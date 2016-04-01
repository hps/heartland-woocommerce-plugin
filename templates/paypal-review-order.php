<?php
/**
 * Review Order
 */
error_log('BEGIN TEMPLATE : PAYPAL-REVIEW-ORDER.PHP');
global $woocommerce;
$checked = get_option('woocommerce_enable_guest_checkout');

//Add hook to show login form or not
$show_login = apply_filters('paypal-for-woocommerce-show-login', !is_user_logged_in() && $checked==="no" && isset($_REQUEST['pp_action']));
?>
<style type="text/css">
    #payment{
        display:none;
    }
</style>

<form method="POST" action="<?php echo add_query_arg( 'pp_action', 'payaction', add_query_arg( 'wc-api', 'WC_Gateway_SecureSubmit_PayPal', home_url( '/' ) ) );?>">

<div id="paypalexpress_order_review">
        <?php woocommerce_order_review();?>
</div>

<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

    <?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

    <?php wc_cart_totals_shipping_html(); ?>

    <?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

<?php endif; ?>

<div class="title">
    <h2><?php _e( 'Customer details', 'woocommerce' ); ?></h2>
</div>

<div class="col2-set addresses">
    <div class="col-1">
        <div class="title">
            <h3><?php _e( 'Billing Address', 'woocommerce' ); ?></h3>
        </div>
        <div class="address">
            <p>
                <?php
                $checkoutForm = maybe_unserialize(WC()->session->checkout_form);
                error_log('$checkoutForm = ' . print_r($checkoutForm,true));
                $myresult = maybe_unserialize(WC()->session->result);
                $isPayPalExpress = $checkoutForm["paypalexpress_initiated"];
                if(isset($checkoutForm['paypalexpress_initiated'])) { 
                    $customer = maybe_unserialize(WC()->session->customer);
                    error_log('paypal-review-order template : $customer = ' . print_r($customer,true));

                    $address = array(
                        'first_name' 	=> $myresult->buyer->firstName,
                        'last_name' 	=> $myresult->buyer->lastName,
                        'address_1'		=> $myresult->shipping->address->address,
                        'city'			=> $myresult->shipping->address->city ,
                        'state'			=> $myresult->shipping->address->state ,
                        'postcode'		=> $myresult->shipping->address->zip,
                        'country'		=> $myresult->shipping->address->country);

                    if(is_user_logged_in()) {
                        $address = array(
                            'first_name' 	=> $myresult->buyer->firstName,
                            'last_name' 	=> $myresult->buyer->lastName,
                            'address_1'		=> isset(WC()->customer->address_1) ? WC()->customer->address_1 : $myresult->shipping->address->address,
                            'city'			=> isset(WC()->customer->city) ? WC()->customer->city :$myresult->shipping->address->city ,
                            'state'			=> isset(WC()->customer->state) ? WC()->customer->state :$myresult->shipping->address->state ,
                            'postcode'		=> isset(WC()->customer->postcode) ? WC()->customer->postcode : $myresult->shipping->address->zip,
                            'country'		=> isset(WC()->customer->country) ? WC()->customer->country :$myresult->shipping->address->country);
                    }
                } else {
                    $address = array(
                        'first_name' => $checkoutForm['billing_first_name'],
                        'last_name' => $checkoutForm['billing_last_name'],
                        'company' => $checkoutForm['billing_company'],
                        'address_1' => $checkoutForm['billing_address_1'],
                        'address_2' => $checkoutForm['billing_address_2'],
                        'city' => $checkoutForm['billing_city'],
                        'state' => $checkoutForm['billing_state'],
                        'postcode' => $checkoutForm['billing_postcode'],
                        'country' => $checkoutForm['billing_country']);
                }
                echo WC()->countries->get_formatted_address( $address );
                ?>
            </p>
        </div>

    </div><!-- /.col-1 -->

<?php if ( WC()->cart->needs_shipping()&& WC()->cart->show_shipping()  ) : ?>

    <div class="col-2">
        <div class="title">
            <h3><?php _e( 'Shipping Address', 'woocommerce' ); ?></h3>
        </div>
        <div class="address">
            <p>
                <?php
                $checkoutForm = maybe_unserialize(WC()->session->checkout_form);
                $myresult = maybe_unserialize(WC()->session->result);

                error_log('session.result = ' . print_r($myresult,true));
                error_log('session.checkout_form = ' . print_r($checkoutForm,true));
                error_log("defined vars : " . print_r(get_defined_vars(),true));

                $address = array(
                    'first_name' 	=> $myresult->shipping->name,
                    'company'		=> isset($checkoutForm["shipping_company"]) ? $checkoutForm["shipping_company"] : '',
                    'address_1'		=> $myresult->shipping->address->address,
                    'city'			=> $myresult->shipping->address->city,
                    'state'			=> $myresult->shipping->address->state,
                    'postcode'		=> $myresult->shipping->address->zip,
                    'country'		=> $myresult->shipping->address->country
                ) ;
                echo WC()->countries->get_formatted_address( $address );
                ?>
            </p>
        </div>
    </div>
<?php endif; ?>
</div>
<?php if ( $show_login ):  ?>
</form>
    <style type="text/css">

        .woocommerce #content p.form-row input.button,
        .woocommerce #respond p.form-row input#submit,
        .woocommerce p.form-row a.button,
        .woocommerce p.form-row button.button,
        .woocommerce p.form-row input.button,
        .woocommerce-page p.form-row #content input.button,
        .woocommerce-page p.form-row #respond input#submit,
        .woocommerce-page p.form-row a.button,
        .woocommerce-page p.form-row button.button,
        .woocommerce-page p.form-row input.button{
            display: block !important;
        }
    </style>
    <div class="title">
        <h2><?php _e( 'Login', 'woocommerce' ); ?></h2>
    </div>
    <form name="" action="" method="post">
        <?php
        function curPageURL() {
            $pageURL = 'http';
            if (@$_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
            $pageURL .= "://";
            if ($_SERVER["SERVER_PORT"] != "80") {
                $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
            } else {
                $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
            }
            return $pageURL;
        }

        woocommerce_login_form(
            array(
                'message'  => 'Please login or create an account to complete your order.',
                'redirect' => curPageURL(),
                'hidden'   => true
            )
        );
        $result = unserialize(WC()->session->RESULT);
        $email = (!empty($_POST['email']))?$_POST['email']:$result['EMAIL'];
        ?>
    </form>
    <div class="title">
        <h2><?php _e( 'Create A New Account', 'woocommerce' ); ?></h2>
    </div>
    <form action="" method="post">
        <p class="form-row form-row-first">
            <label for="paypalexpress_order_review_username">Username:<span class="required">*</span></label>
            <input style="width: 100%;" type="text" name="username" id="paypalexpress_order_review_username" value="" />
        </p>
        <p class="form-row form-row-last">
            <label for="paypalexpress_order_review_email">Email:<span class="required">*</span></label>
            <input style="width: 100%;" type="email" name="email" id="paypalexpress_order_review_email" value="<?php echo $email; ?>" />
        </p>
        <div class="clear"></div>
        <p class="form-row form-row-first">
            <label for="paypalexpress_order_review_password">Password:<span class="required">*</span></label>
            <input type="password" name="password" id="paypalexpress_order_review_password" class="input-text" />
        </p>
        <p class="form-row form-row-last">
            <label for="paypalexpress_order_review_repassword">Re Password:<span class="required">*</span></label>
            <input type="password" name="repassword" id="paypalexpress_order_review_repassword" class="input-text"/>
        </p>
        <div class="clear"></div>
        <p>
            <input class="button" type="submit" name="createaccount" value="Create Account" />
            <input type="hidden" name="address" value="<?php echo WC()->customer->get_address(); ?>">
        </p>
    </form>
<?php else:
        echo '<div class="clear"></div>';
        echo '<p><a class="button" href="' . $woocommerce->cart->get_cart_url() . '">'.__('Cancel order', 'paypal-for-woocommerce').'</a> ';
        echo '<input type="submit" onclick="jQuery(this).attr(\'disabled\', \'disabled\').val(\'Processing\'); jQuery(this).parents(\'form\').submit(); return false;" class="button" value="' . __( 'Place Order','paypal-for-woocommerce') . '" /></p>';
    ?>
    </form><!--close the checkout form-->
<?php endif; ?>
<div class="clear"></div>
<?php
error_log('END TEMPLATE : PAYPAL-REVIEW-ORDER.PHP'); ?>