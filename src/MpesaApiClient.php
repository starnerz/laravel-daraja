<?php

namespace Starnerz\LaravelDaraja;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Starnerz\LaravelDaraja\Exceptions\MpesaApiRequestException;

class MpesaApiClient
{
    /**
     * Guzzle client initialization.
     *
     * @var Client
     */
    protected $client;

    /**
     * Safaricom MPESA APIs application consumer key.
     *
     * @var string
     */
    protected $consumerKey;

    /**
     * Safaricom MPESA APIs application consumer secret.
     *
     * @var string
     */
    protected $consumerSecret;

    /**
     * Access token generated by Safaricom MPESA APIs.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * Identifier organization Map on Safaricom MPESA APIs.
     *
     * @var array
     */
    protected $identifier = [
        'msisdn' => '1', // MSISDN
        'till' => '2', // Till Number
        'paybill' => '4', // Shortcode
    ];

    /**
     * Make the initializations required to make calls to the Safaricom MPESA APIs
     * and throw the necessary exception if there are any missing required
     * configurations.
     */
    public function __construct()
    {
        $this->validateConfigurations();

        $mode = config('laravel-daraja.mode');

        $baseUrl = $this->removeLastSlash(config('laravel-daraja.base_uri.'.$mode));

        $options = [
            'base_uri' => $baseUrl.'/',
            'verify' => $mode === 'sandbox' ? false : true,
        ];

        $this->client = new Client($options);
        $this->consumerKey = config('laravel-daraja.consumer_key');
        $this->consumerSecret = config('laravel-daraja.consumer_secret');
        $this->getAccessToken();
    }

    /**
     * Remove the last forward slush.
     *
     * @param string $url
     * @return string
     */
    protected function removeLastSlash($url)
    {
        return str_replace_last('/', '', $url);
    }

    /**
     * Check if it contains a route name and return full route or
     * return the string assuming its a full URL.
     *
     * @param $urlConfig
     * @return string
     */
    protected function setUrl($urlConfig)
    {
        return Route::has($urlConfig) ? route($urlConfig) : $urlConfig;
    }


    /**
     * Get access token from Safaricom MPESA APIs.
     *
     * @return mixed
     */
    protected function getAccessToken()
    {
        // Set the auth option
        $options = [
            'auth' => [
                $this->consumerKey,
                $this->consumerSecret,
            ],
        ];

        $accessTokenDetails = $this->call('oauth/v1/generate?grant_type=client_credentials', $options, 'GET');
        $this->accessToken = $accessTokenDetails->access_token;
    }

    /**
     * Validate configurations.
     */
    protected function validateConfigurations()
    {
        // Validate keys
        if (empty(config('laravel-daraja.consumer_key'))) {
            throw new \InvalidArgumentException('Consumer key has not been set.');
        }
        if (empty(config('laravel-daraja.consumer_secret'))) {
            throw new \InvalidArgumentException('Consumer secret has not been set');
        }
    }

    /**
     * Generate encrypted security credential.
     *
     * @param $plaintext
     * @return string
     * @internal param null|string $password
     */
    protected function securityCredential($plaintext)
    {
        $publicKey = file_get_contents(__DIR__.'/../cert.cer');

        openssl_public_encrypt($plaintext, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);

        return base64_encode($encrypted);
    }

    /**
     * Make API calls to Safaricom MPESA APIs.
     *
     * @param string $url
     * @param array $options
     * @param string $method
     * @return mixed
     * @throws MpesaApiRequestException
     */
    protected function call($url, $options = [], $method = 'POST')
    {
        if (isset($this->accessToken)) {
            $options['headers'] = ['Authorization' => 'Bearer '.$this->accessToken];
        }

        try {
            $response = $this->client->request($method, $url, $options);

            return json_decode($response->getBody()->getContents());
        } catch (ServerException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
            if (isset($response->Envelope)) {
                $message = 'Safaricom APIs: '.$response->Envelope->Body->Fault->faultstring;
                throw new MpesaApiRequestException($message, $e->getCode());
            }
            throw new MpesaApiRequestException('Safaricom APIs: '.$response->errorMessage, $e->getCode());
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
            throw new MpesaApiRequestException('Safaricom APIs: '
                .$response->errorMessage, $e->getCode());
        }
    }
}
