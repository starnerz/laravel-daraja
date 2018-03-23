<?php

namespace Starnerz\LaravelDaraja\Requests;

use Starnerz\LaravelDaraja\MpesaApiClient;

class Transaction extends MpesaApiClient
{
    /**
     * The transaction status query end point on Safricom API.
     *
     * @var string
     */
    protected $queryEndPoint = 'mpesa/transactionstatus/v1/query';

    /**
     * The initiator's name for the short code.
     *
     * @var string
     */
    protected $initiatorName;

    /**
     * The encrypted initiator's security credential for the short code.
     *
     * @var string
     */
    protected $securityCredential;

    /**
     * The sender of the transaction.
     *
     * @var string
     */
    protected $partyA;

    /**
     * Safaricom API identifier types for organization short code or MSISDN.
     *
     * @var int
     */
    protected $identifierType;

    /**
     * The URL where Safaricom Transaction Status API will send result of the
     * transaction.
     *
     * @var string
     */
    protected $resultURL;

    /**
     * The URL where Safaricom Transaction Status API will send notification of
     * the transaction timing out while in the Safaricom servers queue.
     *
     * @var string
     */
    protected $queueTimeoutURL;

    /**
     * Transaction constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->initiatorName = config('laravel-daraja.initiator.name');
        $this->partyA = config('laravel-daraja.initiator.short_code');
        $this->securityCredential = $this->securityCredential(config('laravel-daraja.initiator.credential'));

        $this->resultURL = $this->setUrl(config('laravel-daraja.result_url.transaction_status'));
        $this->queueTimeoutURL = $this->setUrl(config('laravel-daraja.queue_timeout_url.transaction_status'));
    }

    /**
     * Set different initiator from the one set in the laravel-daraja configurations.
     *
     * @param string $name
     * @param string $securityCredential
     */
    public function setInitiator($name, $securityCredential)
    {
        $this->initiatorName = $name;
        $this->securityCredential = $this->securityCredential($securityCredential);
    }

    /**
     * Set the business short code to use if you want to use a different one
     * from the one set in the configs.
     *
     * @param string $code
     */
    public function setShortCode($code)
    {
        $this->partyA = $code;
    }

    /**
     * Check the transaction status from a business short code to a pay bill number.
     *
     * @param string $transactionID
     * @param string $remarks
     * @param string $occasion
     * @return mixed
     */
    public function toPayBillStatus($transactionID, $remarks, $occasion = '')
    {
        $this->identifierType = $this->identifier['paybill'];

        return $this->status($transactionID, $remarks, $occasion);
    }

    /**
     * Check the transaction status from a business short code to a till number.
     *
     * @param string $transactionID
     * @param string $remarks
     * @param string $occasion
     * @return mixed
     */
    public function toTillStatus($transactionID, $remarks, $occasion = '')
    {
        $this->identifierType = $this->identifier['till'];

        return $this->status($transactionID, $remarks, $occasion);
    }

    /**
     * Check the transaction status from a business short code to a msisdn number.
     *
     * @param string $transactionID
     * @param string $remarks
     * @param string $occasion
     * @return mixed
     */
    public function toMsisdnStatus($transactionID, $remarks, $occasion = '')
    {
        $this->identifierType = $this->identifier['msisdn'];

        return $this->status($transactionID, $remarks, $occasion);
    }

    /**
     * Check the transaction status from a msisdn number to a short code.
     *
     * @param string $msisdn
     * @param string $transactionID
     * @param string $remarks
     * @param string $occasion
     * @return mixed
     */
    public function fromMsisdnStatus($msisdn, $transactionID, $remarks, $occasion = '')
    {
        $this->partyA = $msisdn;
        $this->identifierType = $this->identifier['msisdn'];

        return $this->status($transactionID, $remarks, $occasion);
    }

    /**
     * Send the transaction status query to the Safaricom Transaction
     * Status API.
     *
     * @param string $transactionId
     * @param string $remarks
     * @param string $occasion
     * @return mixed
     */
    protected function status($transactionId, $remarks, $occasion = '')
    {
        $parameters = [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $transactionId,
            'PartyA' => $this->partyA,
            'IdentifierType' => $this->identifierType,
            'ResultURL' => $this->resultURL,
            'QueueTimeOutURL' => $this->queueTimeoutURL,
            'Remarks' => $remarks,
            'Occasion' => $occasion,
        ];

        return $this->call($this->queryEndPoint, ['json' => $parameters]);
    }
}
