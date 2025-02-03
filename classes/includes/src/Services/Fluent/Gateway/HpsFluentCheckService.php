<?php

/**
 * Class HpsFluentCheckService
 */
class HpsFluentCheckService extends HpsSoapGatewayService
{
    /**
     * HpsFluentCheckService constructor.
     *
     * @param null $config
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
    }
    /**
     * @param $config
     *
     * @return $this
     */
    public function withConfig($config)
    {
        $this->_config = $config;
        return $this;
    }
    /**
     * @return \HpsCheckServiceOverrideBuilder
     */
    public function override()
    {
        return new HpsCheckServiceOverrideBuilder($this);
    }
    /**
     * @param null $amount
     *
     * @return \HpsCheckServiceRecurringBuilder
     */
    public function recurring($amount = null)
    {
        $builder = new HpsCheckServiceRecurringBuilder($this);
        return $builder->withAmount($amount);
    }
    /**
     * @return \HpsCheckServiceReturnBuilder
     */
    public function returnCheck()
    {
        return new HpsCheckServiceReturnBuilder($this);
    }
    /**
     * @param null $amount
     *
     * @return \HpsCheckServiceSaleBuilder
     */
    public function sale($amount = null)
    {
        $builder = new HpsCheckServiceSaleBuilder($this);
        return $builder
            ->withAmount($amount);
    }
    /**
     * @return \HpsCheckServiceVoidBuilder
     */
    public function void()
    {
        return new HpsCheckServiceVoidBuilder($this);
    }
    /**
     * @param           $action
     * @param \HpsCheck $check
     * @param           $amount
     * @param null      $clientTransactionId
     * @param bool      $checkVerify
     * @param bool      $achVerify
     *
     * @return mixed
     * @throws \HpsCheckException
     * @throws \HpsInvalidRequestException
     */
    public function _buildTransaction($action, HpsCheck $check, $amount, $clientTransactionId = null, $checkVerify = false, $achVerify = false)
    {
        if ($amount != null) {
            HpsInputValidation::checkAmount($amount);
        }

        if ($check->secCode == HpsSECCode::CCD &&
            ($check->checkHolder == null || $check->checkHolder->checkName == null)) {
            throw new HpsInvalidRequestException(
                esc_html(HpsExceptionCodes::MISSING_CHECK_NAME),
                'For SEC code CCD, the check name is required',
                'check_name'
            );
        }

        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
        $hpsCheckSale = $xml->createElement('hps:CheckSale');
        $hpsBlock1 = $xml->createElement('hps:Block1');

        $hpsBlock1->appendChild($xml->createElement('hps:Amt', sprintf("%0.2f", round($amount, 3))));
        $hpsBlock1->appendChild($this->_hydrateCheckData($check, $xml));
        $hpsBlock1->appendChild($xml->createElement('hps:CheckAction', $action));
        $hpsBlock1->appendChild($xml->createElement('hps:SECCode', $check->secCode));

        if ($checkVerify || $achVerify) {
            $verifyElement = $xml->createElement('hps:VerifyInfo');
            if ($checkVerify) {
                $verifyElement->appendChild($xml->createElement('hps:CheckVerify', ($checkVerify ? 'Y' : 'N')));
            }
            if ($achVerify) {
                $verifyElement->appendChild($xml->createElement('hps:ACHVerify', ($achVerify ? 'Y' : 'N')));
            }
            $hpsBlock1->appendChild($verifyElement);
        }

        if ($check->checkType != null) {
            $hpsBlock1->appendChild($xml->createElement('hps:CheckType', $check->checkType));
        }
        if ($check->dataEntryMode != null) {
            $hpsBlock1->appendChild($xml->createElement('hps:DataEntryMode', $check->dataEntryMode));
        }
        if ($check->checkHolder != null) {
            $hpsBlock1->appendChild($this->_hydrateConsumerInfo($check, $xml));
        }

        $hpsCheckSale->appendChild($hpsBlock1);
        $hpsTransaction->appendChild($hpsCheckSale);

        return $this->_submitTransaction($hpsTransaction, 'CheckSale', $clientTransactionId);
    }
    /**
     * @param      $transaction
     * @param      $txnType
     * @param null $clientTransactionId
     *
     * @return mixed
     * @throws \HpsAuthenticationException
     * @throws \HpsCheckException
     * @throws \HpsGatewayException
     * @throws null
     */
    public function _submitTransaction($transaction, $txnType, $clientTransactionId = null)
    {
        $rsp = $this->doRequest($transaction, $clientTransactionId);
        HpsGatewayResponseValidation::checkResponse($rsp, $txnType);
        $response = HpsCheckResponse::fromDict($rsp, $txnType);

        if ($response->responseCode != 0) {
            throw new HpsCheckException(
                esc_html($rsp->Header->GatewayTxnId),
                esc_html($response->details),
                esc_html($response->responseCode),
                esc_html($response->responseText)
            );
        }

        return $response;
    }
}
