<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 * 
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com)
 * and is licensed under SkaDate Exclusive License by Skalfa LLC.
 * 
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

use \Firebase\JWT\JWT;

class SKANDROID_ACLASS_InAppPurchaseValidator
{
    protected $iis;
    protected $private_key;
    protected $token = null;

    protected $service_account_id;
    protected $service_account_private_key;

    protected $curl;

    const EXPIRATION_TIMEOUT = 600; // timeout in seconds

    const TOKEN_SCOPE = "https://www.googleapis.com/auth/androidpublisher";
    const TOKEN_AUD = "https://www.googleapis.com/oauth2/v4/token";

    const PURCHASE_INFO_URL = "https://www.googleapis.com/androidpublisher/v2/applications/com.skadatexapp/purchases/products/membership_plan_11/tokens/:purchaseToken?access_token=:accessToken";

    const CONFIG_AUTH_TOKEN = "service_account_auth_token";
    const CONFIG_AUTH_TOKEN_EXPIRATION_TIME = "service_account_auth_expiration_time";
    const CONFIG_ACCOUNT_ID = "service_account_id";



    /**
     * @param integer $iis - Service account id
     * @param integer $private_key - Service account private key
     */
    public function __construct($iis, $private_key)
    {
        $this->iis = $iis;
        $this->private_key =  str_replace('\n', "\n", $private_key);

        $this->curl = new \Curl\Curl();
    }

    protected function prepareToken()
    {
        $this->token = OW::getConfig()->getValue('skandroid', self::CONFIG_AUTH_TOKEN);
        $expirationTime = OW::getConfig()->getValue('skandroid', self::CONFIG_ACCOUNT_ID);

        if ( empty($authToken) || $expirationTime > time() + self::EXPIRATION_TIMEOUT - 10 )
        {
            $time = time();

            $token = [
                "iss" => $this->iis,
                "scope" => self::TOKEN_SCOPE,
                "aud" => self::TOKEN_AUD,
                "exp"=> $time+self::EXPIRATION_TIMEOUT,
                "iat"=> $time
            ];

            $jwt = JWT::encode($token, $this->private_key, 'RS256');

            $this->curl->post(self::TOKEN_AUD, array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ));

            $result = json_decode($this->curl->response, true);

            if ( !empty($result['access_token']) )
            {
                $this->token = $result['access_token'];
                OW::getConfig()->saveConfig('skandroid',
                    self::CONFIG_AUTH_TOKEN, $result['access_token']);

                if ( (int) $result['expires_in'] > 10 )
                {
                    OW::getConfig()->saveConfig('skandroid', self::CONFIG_AUTH_TOKEN_EXPIRATION_TIME,
                        ($time + self::EXPIRATION_TIMEOUT - 10) );
                }
                else
                {
                    throw new InvalidTokenException("Can't generate access token");
                }
            }
            else
            {
                throw new InvalidTokenException("Can't generate access token");
            }
        }
    }

    public function getPurchaseInfo($purchaseToken)
    {
        $this->prepareToken();
        $url = str_replace([':purchaseToken', ':accessToken'],
            [$purchaseToken, $this->token], self::PURCHASE_INFO_URL);
        $this->curl->get($url);

        return json_decode($this->curl->response, true);
    }

   public function preValidatePurchase( $purchaseToken, $purchaseTime, $developerPayload )
    {
        /*$purchaseInfo = $this->getPurchaseInfo($purchaseToken);
        $logger = OW::getLogger('skandroid');
        $logger->addEntry(print_r($purchaseInfo, true), 'test');
        $logger->writeLog();

        if ( isset($purchaseInfo['developerPayload']) && $purchaseInfo['developerPayload'] == $developerPayload
            && $purchaseInfo['purchaseState'] == 0
            && $purchaseInfo['consumptionState'] == 0
            && $purchaseInfo['purchaseTimeMillis'] == $purchaseTime )
        {
            return true;
        }

        return false;*/
        return true;
    }

    public function validatePurchase( $purchaseToken, $purchaseTime, $developerPayload )
    {
        /*$purchaseInfo = $this->getPurchaseInfo($purchaseToken);

        if ( $purchaseInfo['developerPayload'] == $developerPayload
            && $purchaseInfo['purchaseState'] == 0
            && $purchaseInfo['consumptionState'] == 1
            && $purchaseInfo['purchaseTimeMillis'] == $purchaseTime )
        {
            return true;
        }

        return false;*/
        return true;
    }
}

class InvalidTokenException extends Exception {

}
