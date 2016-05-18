<?php
/**
 * Payment gateway payment functions
 *
 * PHP version 5.2+
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */

/**
 * Payment gateway payment functions
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */
class WC_Gateway_SecureSubmit_Tests_Integration_Gateway_PaymentTest
    extends WC_Gateway_SecureSubmit_Tests_Utility_UnitTestCase
{
    const TOKEN_FIELD_NAME     = 'securesubmit_token';
    const LAST_FOUR_FIELD_NAME = 'last_four';
    const EXP_MONTH_FIELD_NAME = 'exp_month';
    const EXP_YEAR_FIELD_NAME  = 'exp_year';
    const CARD_TYPE_FIELD_NAME = 'card_type';

    protected $tokenService = null;

    public function setUp()
    {
        parent::setUp();

        $this->tokenService = new HpsTokenService(
            WC_Gateway_SecureSubmit::instance()->public_key
        );

        $this->creditCard = new HpsCreditCard();
        $this->creditCard->number   = '4111111111111111';
        $this->creditCard->expMonth = '12';
        $this->creditCard->expYear  = '2025';
        $this->creditCard->cvv      = '123';
    }

    /** @group external-http */
    public function testPaymentSuccess()
    {
        $this->getTokenAndSetPOST();

        $order = WC_Gateway_SecureSubmit_Tests_Utility_Order::create('securesubmit');
        $result = WC_Gateway_SecureSubmit::instance()->process_payment($order->id);
        $notices = wc_get_notices();

        $this->assertEquals('success', $result['result']);
        $this->assertNotEquals('', $result['redirect']);
        $this->assertEquals(array(), $notices);
    }

    /** @group external-http */
    public function testPaymentFailNoToken()
    {
        $this->getTokenAndSetPOST();
        $_POST[self::TOKEN_FIELD_NAME] = null;

        $order = WC_Gateway_SecureSubmit_Tests_Utility_Order::create('securesubmit');
        $result = WC_Gateway_SecureSubmit::instance()->process_payment($order->id);
        $notices = wc_get_notices();

        $this->assertEquals('fail', $result['result']);
        $this->assertEquals('', $result['redirect']);
        $this->assertNotEquals(array(), $notices);
    }

    protected function getTokenAndSetPOST()
    {
        $token = $this->tokenService->getToken($this->creditCard);
        $_POST[self::TOKEN_FIELD_NAME]     = $token->token_value;
        $_POST[self::LAST_FOUR_FIELD_NAME] = substr($this->creditCard->number, -4);
        $_POST[self::EXP_MONTH_FIELD_NAME] = $this->creditCard->expMonth;
        $_POST[self::EXP_YEAR_FIELD_NAME]  = $this->creditCard->expYear;
        $_POST[self::CARD_TYPE_FIELD_NAME] = 'Visa';
    }
}
