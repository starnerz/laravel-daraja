<?php

namespace Starnerz\LaravelDaraja\Requests;

use Starnerz\LaravelDaraja\MpesaApiClient;

class C2B extends MpesaApiClient
{
    /**
     * The Safaricom C2B API end point for registering the confirmation
     * and validation URLs.
     *
     * @var string
     */
    protected $urlRegistrationEndPoint = 'mpesa/c2b/v1/registerurl';

    /**
     * The Safaricom C2B API end point for simulating a C2B transaction.
     *
     * @var string
     */
    protected $simulationEndpoint = 'mpesa/c2b/v1/simulate';

    /**
     * The Safaricom C2B API command ID.
     *
     * @var string
     */
    protected $commandID;

    /**
     * C2B constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Register the confirmation and validation URLs to the Safaricom C2B API.
     *
     * @param  string  $confirmationUrl
     * @param  string  $validationUrl
     * @param  string  $responseType
     * @param  null|string|int  $shortCode
     * @return mixed
     */
    public function registerUrls($confirmationUrl, $validationUrl, $responseType = 'Completed', $shortCode = null)
    {
        $parameters = [
            'ShortCode' => is_null($shortCode) ? config('laravel-daraja.initiator.short_code') : $shortCode,
            'ResponseType' => $responseType,
            'ConfirmationURL' => $this->setUrl($confirmationUrl),
            'ValidationURL' => $this->setUrl($validationUrl),
        ];

        return $this->call($this->urlRegistrationEndPoint, ['json' => $parameters]);
    }

    /**
     * Set the command ID to be used for the transaction.
     *
     * @param  string  $commandId
     */
    public function setCommandId($commandId)
    {
        $this->commandID = $commandId;
    }

    /**
     * Simulate customer payment to a pay bill number through Safaricom C2B API.
     *
     * @param  string  $phoneNumber
     * @param  string  $amount
     * @param  string  $reference
     * @param  null|string  $shortCode
     * @return mixed
     */
    public function simulatePaymentToPaybill($phoneNumber, $amount, $reference, $shortCode = null)
    {
        $this->setCommandId('CustomerPayBillOnline');

        return $this->simulate($phoneNumber, $amount, $reference, $shortCode);
    }

    /**
     * Simulate customer payment to a till number through Safaricom C2B API.
     *
     * @param  string  $phoneNumber
     * @param  string  $amount
     * @param  string  $reference
     * @param  null|string  $shortCode
     * @return mixed
     */
    public function simulatePaymentToTill($phoneNumber, $amount, $reference, $shortCode = null)
    {
        $this->setCommandId('CustomerBuyGoodsOnline');

        return $this->simulate($phoneNumber, $amount, $reference, $shortCode);
    }

    /**
     * Send the transaction to be simulated to the Safaricom C2B API.
     *
     * @param $phoneNumber
     * @param $amount
     * @param $reference
     * @param  null  $shortCode
     * @return mixed
     */
    protected function simulate($phoneNumber, $amount, $reference, $shortCode = null)
    {
        $parameters = [
            'ShortCode' => is_null($shortCode) ? config('laravel-daraja.initiator.short_code') : $shortCode,
            'CommandID' => $this->commandID,
            'Amount' => $amount,
            'Msisdn' => $phoneNumber,
            'BillRefNumber' => $reference,
        ];

        return $this->call($this->simulationEndpoint, ['json' => $parameters]);
    }
}
