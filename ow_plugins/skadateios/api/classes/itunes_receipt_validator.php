<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.skadateios.api.classes
 * @since 1.0
 */
class SKADATEIOS_ACLASS_ItunesReceiptValidator
{
    const ITUNES_PRODUCTION_VERIFY_URL = 'https://buy.itunes.apple.com/verifyReceipt';
    const ITUNES_SANDBOX_VERIFY_URL = 'https://sandbox.itunes.apple.com/verifyReceipt';

    private $mode = 'live';
    private $endpoint = null;
    private $sharedSecret = null;

    private $retrySandbox = true;
    private $retryProduction = false;

    const COULD_NOT_READ_JSON_OBJECT               = 21000; // The App Store could not read the JSON object you provided.
    const RECEIPT_DATA_PROPERTY_MALFORMED          = 21002; // The data in the receipt-data property was malformed.
    const RECEIPT_COULD_NOT_BE_AUTHENTICATED       = 21003; // The receipt could not be authenticated.
    const SHARED_SECRET_MISMATCH                   = 21004; // The shared secret you provided does not match the shared secret on file for your account.
    const RECEIPT_SERVER_UNAVAILABLE               = 21005; // The receipt server is not currently available.
    const VALID_RECEIPT_BUT_SUBSCRIPTION_EXPIRED   = 21006; // This receipt is valid but the subscription has expired. When this status code is returned to your server, the receipt data is also decoded and returned as part of the response.
    const SANDBOX_RECEIPT_SENT_TO_PRODUCTION_ERROR = 21007; // This receipt is a sandbox receipt, but it was sent to the production service for verification.
    const PRODUCTION_RECIEPT_SENT_TO_SANDBOX_ERROR = 21008; // This receipt is a production receipt, but it was sent to the sandbox service for verification.
    const RECEIPT_VALID                            = 0;     // This receipt valid.
    const CURL_ERROR                               = 60001;

    function __construct( $mode, $secret )
    {
        $this->mode = $mode;

        switch ( $this->mode )
        {
            case 'test':
                $this->endpoint = self::ITUNES_SANDBOX_VERIFY_URL;
                break;

            case 'live':
                $this->endpoint = self::ITUNES_PRODUCTION_VERIFY_URL;
                break;
        }

        $this->sharedSecret = $secret;
    }

    public function validateReceipt( $receipt )
    {
        $ch = curl_init($this->endpoint);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        $receiptData = (object) array(
            'receipt-data' => $receipt,
        );

        if ( $this->sharedSecret != null )
        {
            $receiptData->password = $this->sharedSecret;
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($receiptData));

        $response = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Ensure the http status code was 200
        if ( $httpStatus != 200 )
        {
            return false;
        }

        // Parse the response data
        $data = json_decode($response, true);

        // Ensure response data was a valid JSON string
        if ( !is_array($data) )
        {
            return false;
        }

        if ( $this->mode == 'test' && $data['status'] === self::SANDBOX_RECEIPT_SENT_TO_PRODUCTION_ERROR && $this->retrySandbox )
        {
            return $this->validateReceipt($receipt);
        }

        if ( $this->mode == 'live' && $data['status'] === self::PRODUCTION_RECIEPT_SENT_TO_SANDBOX_ERROR && $this->retryProduction )
        {
            return $this->validateReceipt($receipt);
        }

        return $data;
    }
}