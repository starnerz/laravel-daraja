<?php

namespace Starnerz\LaravelDaraja\Requests;

use Starnerz\LaravelDaraja\MpesaApiClient;

class B2C extends MpesaApiClient
{
    /**
     * Safaricom APIs B2C endpoint.
     *
     * @var string
     */
    protected $endPoint = 'mpesa/b2c/v1/paymentrequest';

    /**
     * Safaricom APIs B2C command id.
     *
     * @var string
     */
    protected $commandId;

    /**
     * Safaricom APIs initiator short code username.
     *
     * @var string
     */
    protected $initiatorName;

    /**
     * Safaricom APIs B2C encrypted initiator short code password.
     *
     * @var string
     */
    protected $securityCredential;

    /**
     * Safaricom APIs B2C initiator short code.
     *
     * @var string
     */
    protected $partyA;

    /**
     * Safaricom APIs B2C queue timeout URI.
     *
     * @var string
     */
    protected $queueTimeOutURL;

    /**
     * Where the Safaricom B2C API will post the result of the transaction.
     *
     * @var string
     */
    protected $resultURL;

    /**
     * Necessary initializations for B2C transactions from the config file while
     * also initialize parent constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->initiatorName = config('laravel-daraja.initiator.name');
        $this->securityCredential = $this->securityCredential(config('laravel-daraja.initiator.credential'));
        $this->partyA = config('laravel-daraja.initiator.short_code');

        $this->queueTimeOutURL = $this->setUrl(config('laravel-daraja.queue_timeout_url.b2c'));
        $this->resultURL = $this->setUrl(config('laravel-daraja.result_url.b2c'));
    }

    /**
     * Set the initiator short code credentials.
     *
     * @param string $name
     * @param string $credential
     */
    public function setInitiator($name, $credential)
    {
        $this->initiatorName = $name;
        $this->securityCredential = $this->securityCredential($credential);
    }

    /**
     * Set the command ID used by the Safaricom B2C API.
     *
     * @param string $command
     */
    public function setCommandId($command)
    {
        $this->commandId = $command;
    }

    /**
     * Set the short code to be used for B2C transaction.
     *
     * @param string $sender
     */
    public function setSender($sender)
    {
        $this->partyA = $sender;
    }

    /**
     * Set the URI where Safaricom B2C API will send notification
     * transaction timed out on queue.
     *
     * @param string $url
     */
    public function setQueTimeoutUrl($url)
    {
        $this->queueTimeOutURL = $url;
    }

    /**
     * Set the URI where Safaricom B2C API will send result of the transaction.
     *
     * @param string $url
     */
    public function setResultUrl($url)
    {
        $this->resultURL = $url;
    }

    /**
     * Make a request to Safaricom B2C API with command type SalaryPayment.
     *
     * @param string $recipient
     * @param int $amount
     * @param string $remarks
     * @param string $occasion
     * @return mixed|string
     */
    public function salaryPayment($recipient, $amount, $remarks, $occasion = '')
    {
        $this->setCommandId('SalaryPayment');

        return $this->pay($recipient, $amount, $remarks, $occasion);
    }

    /**
     * Make a request to Safaricom B2C API with command type BusinessPayment.
     *
     * @param string $recipient
     * @param int $amount
     * @param string $remarks
     * @param string $occasion
     * @return mixed|string
     */
    public function businessPayment($recipient, $amount, $remarks, $occasion = '')
    {
        $this->setCommandId('BusinessPayment');

        return $this->pay($recipient, $amount, $remarks, $occasion);
    }

    /**
     * Make a request to Safaricom B2C API with command type PromotionPayment.
     *
     * @param string $recipient
     * @param int $amount
     * @param string $remarks
     * @param string $occasion
     * @return mixed|string
     */
    public function promotionPayment($recipient, $amount, $remarks, $occasion = '')
    {
        $this->setCommandId('PromotionPayment');

        return $this->pay($recipient, $amount, $remarks, $occasion);
    }

    /**
     * Send transaction details to Safaricom B2C API.
     *
     * @param string $recipient
     * @param int $amount
     * @param string $remarks
     * @param string $occasion
     * @return mixed|string
     */
    protected function pay($recipient, $amount, $remarks, $occasion = '')
    {
        $parameters = [
            'InitiatorName' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => $this->commandId,
            'Amount' => $amount,
            'PartyA' => $this->partyA,
            'PartyB' => $recipient,
            // As per the safaricom B2C API specification the $remarks can not be more than 100 characters
            'Remarks' => str_limit($remarks, 100, ''),
            'QueueTimeOutURL' => $this->queueTimeOutURL,
            'ResultURL' => $this->resultURL,
            'Occassion' => $occasion,
        ];

        return $response = $this->call($this->endPoint, ['json' => $parameters]);
    }
}