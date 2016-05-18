<?php
/**
 * Payment gateway support functions
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
 * Payment gateway support functions
 *
 * @category Tests
 * @package  WC_Gateway_SecureSubmit
 * @author   Heartland <EntApp_DevPortal@e-hps.com>
 * @license  Custom <https://github.com/hps/heartland-woocommerce-plugin/blob/master/LICENSE.md>
 * @link     https://github.com/hps/heartland-woocommerce-plugin
 */
class WC_Gateway_SecureSubmit_Tests_Unit_Gateway_SupportTest
    extends WC_Gateway_SecureSubmit_Tests_Utility_UnitTestCase
{
    public function testInstance()
    {
        $this->assertEquals(
            WC_Gateway_SecureSubmit::instance(),
            WC_Gateway_SecureSubmit::instance()
        );
        $this->assertEquals(
            $this->instance,
            WC_Gateway_SecureSubmit::instance()
        );
        $this->assertNotEquals(
            null,
            WC_Gateway_SecureSubmit::instance()
        );
    }

    public function testUtf8BadHandle()
    {
        $tagSrc = '<tag src="/url" />';
        $tagNoSrc = '<tag att="val" />';

        $this->assertEquals(
            $tagSrc,
            $this->instance->utf8($tagSrc, 'nope')
        );
        $this->assertEquals(
            $tagNoSrc,
            $this->instance->utf8($tagNoSrc, 'nope')
        );
    }

    public function testUtf8GoodHandle()
    {
        $tagSrc = '<tag src="/url" />';
        $tagNoSrc = '<tag att="val" />';

        $this->assertEquals(
            str_replace(' src', ' charset="utf-8" src', $tagSrc),
            $this->instance->utf8($tagSrc, 'securesubmit')
        );
        $this->assertEquals(
            $tagNoSrc,
            $this->instance->utf8($tagNoSrc, 'securesubmit')
        );
    }

    public function testChecksNotEnabled()
    {
        $enabled = $this->instance->enabled;
        $this->instance->enabled = 'no';

        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'checks')
        );
        $this->assertEquals('', $result);

        $this->instance->enabled = $enabled;
    }

    public function testCheckedNoKeys()
    {
        $skey = $this->instance->secret_key;
        $pkey = $this->instance->public_key;
        $this->instance->secret_key = null;
        $this->instance->public_key = null;

        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'checks')
        );
        $this->assertNotEquals('', $result);

        $this->instance->secret_key = $skey;
        $this->instance->public_key = $pkey;
    }

    public function testCheckedNoPublicKey()
    {
        $pkey = $this->instance->public_key;
        $this->instance->public_key = null;

        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'checks')
        );
        $this->assertNotEquals('', $result);

        $this->instance->public_key = $pkey;
    }

    public function testCheckedNoSecretKey()
    {
        $skey = $this->instance->secret_key;
        $this->instance->secret_key = null;

        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'checks')
        );
        $this->assertNotEquals('', $result);

        $this->instance->secret_key = $skey;
    }

    public function testChecksEnabled()
    {
        $result = WC_Gateway_SecureSubmit_Tests_Utility_Helper::captureOutput(
            array($this->instance, 'checks')
        );
        $this->assertEquals('', $result);
    }
}
