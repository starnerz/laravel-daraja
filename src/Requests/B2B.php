<?php

namespace Starnerz\LaravelDaraja\Requests;

use Starnerz\LaravelDaraja\MpesaApiClient;

class B2B extends MpesaApiClient
{
    /**
     * Safaricom APIs B2B endpoint.
     *
     * @var string
     */
    protected $endPoint = 'mpesa/b2b/v1/paymentrequest';

    /**
     * The initiator's short code.
     *
     * @var string
     */
    protected $partyA;

    /**
     * Initiator business short code name.
     *
     * @var string
     */
    protected $initiatorName;

    /**
     * Initiator encrypted security credential.
     *
     * @var string
     */
    protected $securityCredential;

    /**
     * Safaricom B2B API command ID.
     *
     * @var string
     */
    protected $commandId;

    /**
     * The Safaricom API identifier type for the sender.
     *
     * @var int
     */
    protected $senderIdentifierType;

    /**
     * The Safaricom API identifier type for the receiver.
     *
     * @var int
     */
    protected $receiverIdentifierType;

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
     * Necessary initializations for B2B transactions from the config file while
     * also initialize parent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initiatorName = config('laravel-daraja.initiator.name');
        $this->securityCredential = $this->securityCredential(config('laravel-daraja.initiator.credential'));
        $this->partyA = config('laravel-daraja.initiator.short_code');
        $this->senderIdentifierType = $this->identifier[config('laravel-daraja.initiator.type')];

        $this->queueTimeOutURL = $this->setUrl(config('laravel-daraja.queue_timeout_url.b2b'));
        $this->resultURL = $this->setUrl(config('laravel-daraja.result_url.b2b'));
    }

    /**
     * Set the initiator short code credentials.
     *
     * @param  string  $name
     * @param  string  $credential
     */
    public function setInitiator($name, $credential)
    {
        $this->initiatorName = $name;
        $this->securityCredential = $this->securityCredential($credential);
    }

    /**
     * Set the command ID used by the Safaricom B2B API.
     *
     * @param  string  $command
     */
    public function setCommandId($command)
    {
        $this->commandId = $command;
    }

    /**
     * Set the short code and type of code to be used for B2B transaction.
     *
     * @param  string  $code
     * @param  string  $type  valid types are paybill, till, msisdn
     */
    public function setShortCode($code, $type)
    {
        $this->partyA = $code;
        $this->senderIdentifierType = $this->identifier[$type];
    }

    /**
     * Set the URI where Safaricom B2B API will send notification
     * transaction timed out on queue.
     *
     * @param  string  $url
     */
    public function setQueTimeoutUrl($url)
    {
        $this->queueTimeOutURL = $url;
    }

    /**
     * Set the URI where Safaricom B2B API will send result of the transaction.
     *
     * @param  string  $url
     */
    public function setResultUrl($url)
    {
        $this->resultURL = $url;
    }

    /**
     * Make a payment to a pay bill number from a business short code
     * which in this case is a Pay bill number or a till number.
     *
     * @param  string  $payBillNo
     * @param  int  $amount
     * @param  string  $remarks
     * @param  string  $accountReference
     * @return mixed
     */
    public function payToPayBill($payBillNo, $amount, $remarks, $accountReference = '')
    {
        $this->setCommandId('BusinessPayBill');
        $this->receiverIdentifierType = $this->identifier['paybill'];

        return $this->pay($payBillNo, $amount, $remarks, $accountReference);
    }

    /**
     * Make a payment to a lipa na mpesa till number from a business short code
     * which in this case is a Pay bill number or a till number.
     *
     * @param  string  $tillNo
     * @param  int  $amount
     * @param  string  $remarks
     * @return mixed
     */
    public function payToBuyGoods($tillNo, $amount, $remarks)
    {
        $this->setCommandId('BusinessBuyGoods');
        $this->receiverIdentifierType = $this->identifier['till'];

        return $this->pay($tillNo, $amount, $remarks);
    }

    /**
     * Send transaction details to Safaricom B2B API.
     *
     * @param  string  $business  Till Number or short code
     * @param  int  $amount
     * @param  string  $remarks
     * @param  string  $accountReference
     * @return mixed
     */
    protected function pay($business, $amount, $remarks, $accountReference = '')
    {
        $parameters = [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => $this->commandId,
            'SenderIdentifierType' => $this->senderIdentifierType,
            'RecieverIdentifierType' => $this->receiverIdentifierType,
            'Amount' => $amount,
            'PartyA' => $this->partyA,
            'PartyB' => $business,
            // As per the safaricom B2C API specification the $accountReference
            // can not be more than 20 characters
            'AccountReference' => str_limit($accountReference, 20, ''),
            // As per the safaricom B2C API specification the $remarks
            // can not be more than 100 characters
            'Remarks' => str_limit($remarks, 100, ''),
            'QueueTimeOutURL' => $this->queueTimeOutURL,
            'ResultURL' => $this->resultURL,
        ];

        return $response = $this->call($this->endPoint, ['json' => $parameters]);
    }
}
