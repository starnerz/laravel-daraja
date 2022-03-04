<?php

namespace Starnerz\LaravelDaraja\Requests;

use Starnerz\LaravelDaraja\MpesaApiClient;

class Reversal extends MpesaApiClient
{
    /**
     * The Safaricom Reversal API end point for reversing a MPESA transaction.
     *
     * @var string
     */
    protected $reversalEndPoint = 'mpesa/reversal/v1/request';

    /**
     * The Safaricom short code initiator name.
     *
     * @var string
     */
    protected $initiatorName;

    /**
     * The encrypted initiator security credential.
     *
     * @var string
     */
    protected $securityCredential;

    /**
     * The URL where Safaricom Reversal API will send result of the transaction.
     *
     * @var string
     */
    protected $resultURL;

    /**
     * The URL where Safaricom Reversal API will send notification of the transaction
     * timing out while in the Safaricom servers queue.
     *
     * @var string
     */
    protected $queueTimeoutURL;

    /**
     * Reversal constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->initiatorName = config('laravel-daraja.initiator.name');
        $this->securityCredential = $this->securityCredential(config('laravel-daraja.initiator.credential'));

        $this->resultURL = $this->setUrl(config('laravel-daraja.result_url.reversal'));
        $this->queueTimeoutURL = $this->setUrl(config('laravel-daraja.queue_timeout_url.reversal'));
    }

    /**
     * Set initiator other than the one in the laravel-daraja config file.
     *
     * @param  string  $initiatorName
     * @param  string  $securityCredential
     */
    public function setInitiator($initiatorName, $securityCredential)
    {
        $this->initiatorName = $initiatorName;
        $this->securityCredential = $this->securityCredential($securityCredential);
    }

    /**
     * Set the url that will handle the timeout response from the
     * MPESA Reversal API.
     *
     * @param  string  $url
     */
    public function setQueueTimeoutURL($url)
    {
        $this->queueTimeoutURL = $url;
    }

    /**
     * Set the url that will handle the result of the transaction
     * from the MPESA Reversal API.
     *
     * @param  string  $url
     */
    public function setResultURL($url)
    {
        $this->resultURL = $url;
    }

    /**
     * Make a request to reverse a transaction to the Safaricom MPESA Reversal API.
     *
     * @param  string  $transactionId
     * @param  string  $amount
     * @param  string  $remarks
     * @param  null|string  $shortCode
     * @param  string  $occasion
     * @return mixed
     */
    public function reverse($transactionId, $amount, $remarks, $shortCode = null, $occasion = '')
    {
        $parameters = [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => 'TransactionReversal',
            'TransactionID' => $transactionId,
            'Amount' => $amount,
            'ReceiverParty' => is_null($shortCode) ? config('laravel-daraja.initiator.short_code') : $shortCode,
            'RecieverIdentifierType' => '4',
            'ResultURL' => $this->resultURL,
            'QueueTimeoutURL' => $this->queueTimeoutURL,
            'Remarks' => str_limit($remarks, 100, ''),
            'Occasion' => str_limit($occasion, 100, ''),
        ];

        return $this->call($this->reversalEndPoint, ['json' => $parameters]);
    }
}
