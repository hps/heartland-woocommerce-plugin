<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_Gateway_SecureSubmit_MasterPass_Data
{
    protected $masterpass = null;

    public function __construct(&$masterpass = null)
    {
      $this->masterpass = $masterpass;
    }

    /**
     * Gets a configured service
     *
     * @return HpsMasterPassService
     */
    public function getService()
    {
        $config = new HpsCentinelConfig();
        $config->processorId    = 475;
        $config->merchantId     = $this->masterpass->merchantId;
        $config->transactionPwd = $this->masterpass->transactionPwd;
        return new HpsMasterPassService($config);
    }

    /**
     * Used in `masterpass-review-order.php` to format `HpsBuyerData` and
     * `HpsShippingInfo` as HTML.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function getFormattedAddress($data)
    {
        $address = $this->getWCAddress($data);
        return WC()->countries->get_formatted_address($address);
    }

    /**
     * Gets generic address array from `HpsBuyerData`/`HpsShippingInfo`
     *
     * @param mixed $data
     *
     * @return array
     */
    public function getWCAddress($data)
    {
        return array(
            'first_name' => $data->firstName,
            'last_name'  => $data->lastName,
            'address_1'  => $data->address->address,
            'city'       => $data->address->city,
            'state'      => $data->address->state,
            'postcode'   => $data->address->zip,
            'country'    => $data->countryCode,
        );
    }

    /**
     * Gets a mapped `HpsBuyerData` object
     *
     * @param array $checkoutForm
     *
     * @return HpsBuyerData
     */
    public function getBuyerData($checkoutForm)
    {
        $data = new HpsBuyerData();
        $data->firstName = $checkoutForm['billing_first_name'];
        $data->lastName = $checkoutForm['billing_last_name'];
        $data->address = new HpsAddress();
        // $data->address->company => $checkoutForm['billing_company'];
        $data->address->address = $checkoutForm['billing_address_1'] . ' ' . $checkoutForm['billing_address_2'];
        $data->address->city = $checkoutForm['billing_city'];
        $data->address->state = $checkoutForm['billing_state'];
        $data->address->zip = $checkoutForm['billing_postcode'];
        $data->countryCode = $checkoutForm['billing_country'];
        return $data;
    }

    /**
     * Gets a mapped `HpsShippingInfo` object
     *
     * @param array $checkoutForm
     *
     * @return HpsShippingInfo
     */
    public function getShippingInfo($checkoutForm)
    {
        $data = new HpsShippingInfo();
        $data->address = new HpsAddress();

        if (isset($checkoutForm['ship_to_different_address']) && $checkoutForm['ship_to_different_address'] === true) {
            $data->firstName = WC()->customer->shiptoname;
            $data->address->address = WC()->customer->get_address() . ' ' . WC()->customer->get_address_2();
            $data->address->city = WC()->customer->get_city();
            $data->address->state = WC()->customer->get_state();
            $data->address->postcode = WC()->customer->get_postcode();
            $data->address->country = WC()->customer->get_country();
        } else {
            $data->firstName = $checkoutForm['billing_first_name'];
            $data->lastName = $checkoutForm['billing_last_name'];
            // $data->address->company => $checkoutForm['billing_company'];
            $data->address->address = $checkoutForm['billing_address_1'] . ' ' . $checkoutForm['billing_address_2'];
            $data->address->city = $checkoutForm['billing_city'];
            $data->address->state = $checkoutForm['billing_state'];
            $data->address->zip = $checkoutForm['billing_postcode'];
            $data->countryCode = $checkoutForm['billing_country'];
        }

        return $data;
    }

    /**
     * Gets a mapped set of `HpsLineItem` objects
     *
     * @return array
     */
    public function getLineItems($cart)
    {
        $items = array();

        foreach ($cart->get_cart() as $cartItem) {
            $item = new HpsLineItem();
            $item->number = $cartItem['product_id'];
            $item->name = $cartItem['data']->post->post_title;
            $item->description = $cartItem['data']->post->post_excerpt;
            $item->quantity = $cartItem['quantity'];
            $item->amount = $cartItem['line_total'] / $item->quantity;
            $items[] = $item;
        }

        return $items;
    }
}
