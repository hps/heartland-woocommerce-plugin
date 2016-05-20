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
class WC_Gateway_SecureSubmit_Tests_Unit_Plugin
    extends WC_Gateway_SecureSubmit_Tests_Utility_UnitTestCase
{
    protected $targetClass  = 'WC_Gateway_SecureSubmit';

    public function setUp()
    {
        parent::setUp();
        $this->instance = new WooCommerceSecureSubmitGateway();
    }

    public function testInit()
    {
        $this->instance->init();
        $this->assertTrue(true);
    }

    public function testInitWith()
    {
        $this->instance->init();
        $this->assertTrue(true);
    }

    public function testActivate()
    {
        $this->instance->activate();
        $this->assertTrue(true);
    }

    public function testAddPayPalExpressOptionNotInitiated()
    {
        $this->markTestSkipped('TODO: find how to set settings manually');
        $settings = get_option('woocommerce_heartland_paypal_settings', array());
        $settings['enabled'] = 'yes';

        $result = $this->instance->add_paypal_express_option();
        $this->assertNotEquals('', $result);
    }

    public function testPayPalExpressButtonHtml()
    {
        $result = $this->instance->paypal_express_button_html();
        $this->assertNotEquals('', $result);
    }

    public function testSavedCards()
    {
        $user = $this->factory->user->create();
        wp_set_current_user($user);
        add_user_meta(
            $user,
            '_secure_submit_card',
            array(
                'card_type'   => 'test',
                'exp_month'   => '12',
                'exp_year'    => '2025',
                'last_four'   => '1111',
                'token_value' => 'blah',
            )
        );

        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'savedCards')
        );
        $this->assertNotEquals('', $result);
    }

    public function testSavedCardsDeleteCard()
    {
        $user = $this->factory->user->create();
        wp_set_current_user($user);
        add_user_meta(
            $user,
            '_secure_submit_card',
            array(
                'card_type'   => 'test',
                'exp_month'   => '12',
                'exp_year'    => '2025',
                'last_four'   => '1111',
                'token_value' => 'blah',
            )
        );
        add_user_meta(
            $user,
            '_secure_submit_card',
            array(
                'card_type'   => 'test',
                'exp_month'   => '12',
                'exp_year'    => '2025',
                'last_four'   => '1111',
                'token_value' => 'blah',
            )
        );
        $_POST['_wpnonce'] = wp_create_nonce('secure_submit_del_card');
        $_POST['delete_card'] = '0';

        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'savedCards')
        );
        $this->assertNotEquals('', $result);
    }

    public function testSavedCardsDeleteCardReturnsEarly()
    {
        $user = $this->factory->user->create();
        wp_set_current_user($user);
        add_user_meta(
            $user,
            '_secure_submit_card',
            array(
                'card_type'   => 'test',
                'exp_month'   => '12',
                'exp_year'    => '2025',
                'last_four'   => '1111',
                'token_value' => 'blah',
            )
        );
        $_POST['_wpnonce'] = wp_create_nonce('secure_submit_del_card');
        $_POST['delete_card'] = '0';

        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'savedCards')
        );
        $this->assertEquals('', $result);
    }

    public function testSavedCardsNoCards()
    {
        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'savedCards')
        );
        $this->assertEquals('', $result);
    }

    public function testLoadScripts()
    {
        $this->markTestSkipped('TODO: find way to set current page');
        $_GET['p'] = wc_get_page_id('myaccount');
        $this->instance->loadScripts();
        $this->assertTrue(true);
    }

    public function testLoadScriptsNotOnMyAccountPage()
    {
        $this->instance->loadScripts();
        $this->assertTrue(true);
    }

    public function testSetPayPalInitStyles()
    {
        $this->markTestSkipped('TODO: find way to set current page');
        $_GET['p'] = wc_get_page_id('review_order');
        $this->instance->set_paypal_init_styles();
        $this->assertTrue(true);
    }

    public function testSetPayPalInitStylesNotReviewOrderPage()
    {
        $this->instance->set_paypal_init_styles();
        $this->assertTrue(true);
    }

    /**
     * @group             external-http
     * @expectedException HpsException
     */
    public function testCheckUrlForPayPalParams()
    {
        $_GET['pp_action'] = 'revieworder';
        $_GET['token'] = 'badtoken';
        $this->instance->check_url_for_paypal_parms();
        $this->assertTrue(true);
    }

    public function testCheckUrlForPayPalParamsWithoutPPAction()
    {
        $this->instance->check_url_for_paypal_parms();
        $this->assertTrue(true);
    }

    public function testSetPayPalReviewOrderShortcode()
    {
        $result = $this->instance->set_paypal_review_order_shortcode(array());
        $this->assertNotEquals('', $result);
    }

    public function testGetPayPalReviewOrderPage()
    {
        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'get_paypal_review_order_page')
        );
        $this->assertNotEquals('', $result);
    }
}
