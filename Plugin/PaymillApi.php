<?php

namespace Fm\PaymentPaymillBundle\Plugin;

/**
 * Wrapper around Paymill's PHP API.
 */
class PaymillApi
{
    const apiEndpoint = 'https://api.paymill.com/v2/';

    private $clients;
    private $transactions;

    public function __construct ($apiKey)
    {
        $this->clients      = new \Services_Paymill_Clients($apiKey, self::apiEndpoint);
        $this->transactions = new \Services_Paymill_Transactions($apiKey, self::apiEndpoint);
    }

    /**
     * Get the client for future requests.
     * If no client definition is found, the client will be set to null. A new
     * client will be created if none is found for the given 'email'.
     *
     * @param array $data Array containing a mandatory 'email' key and an
     *                    optional 'description' key.
     * @throws PaymillException If a request failed
     */
    public function getClient ($data)
    {
        if (!is_array($data) || !isset($data['email'])) {
            return null;
        }

        $email = $data['email'];

        $description = null;
        if (isset($data['description'])) {
            $description = $data['description'];
        }

        $this->checkResponse(
            $client = $this->clients->get(array('email' => $email))
        );

        if (!is_array($client) || count($client) === 0) {
            // client not found, create a new one
            $this->checkResponse(
                $client = $this->clients->create(array(
                    'email'       => $email,
                    'description' => $description
                ))
            );
        } else if (count($client) === 1) {
            // the client already exists
            $client = $client[0];
        } else {
            // more than one client was found
            $client = null;
        }

        return $client;
    }

    /**
     * Create a transaction.
     *
     * @param  array   $client      Associated client (or null)
     * @param  array   $token       One-time token
     * @param  integer $amount      Amount (in cents) which will be charged
     * @param  string  $currency    ISO 4217 formatted currency code
     * @param  string  $description A short description for the transaction (optional)
     *
     * @return array Created transaction
     * @throws PaymillException If the request failed
     */
    public function createTransaction ($client, $token, $amount, $currency, $description = null)
    {
        $this->checkResponse(
            $transaction = $this->transactions->create(array(
                'client'      => $client !== null ? $client['id'] : null,
                'token'       => $token,
                'amount'      => $amount,
                'currency'    => $currency,
                'description' => $description
            ))
        );

        return $transaction;
    }

    /**
     * Throw an exception if a request failed.
     *
     * @param array $response Response to the request
     * @throws PaymillException If the request failed
     */
    private function checkResponse ($response)
    {
        if (!isset($response['error'])) {
            return;
        }

        throw new PaymillException($response);
    }
}