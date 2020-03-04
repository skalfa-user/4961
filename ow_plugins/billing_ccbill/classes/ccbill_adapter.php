<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * CCBill billing gateway adapter class.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.billing_ccbill.classes
 * @since 1.0
 */
class BILLINGCCBILL_CLASS_CcbillAdapter implements OW_BillingAdapter
{
    const GATEWAY_KEY = 'billingccbill';

    const DATALINK_URL = 'https://datalink.ccbill.com/data/main.cgi';

    const FORM_ACTION_URL = 'https://bill.ccbill.com/jpost/signup.cgi';

    /**
     * @var BOL_BillingService
     */
    private $billingService;
    private static $currencies = array(
        'USD' => 840, 'EUR' => 978, 'AUD' => 036, 'CAD' => 124, 'GBP' => 826, 'JPY' => 392
    );

    public function __construct()
    {
        $this->billingService = BOL_BillingService::getInstance();
    }

    public function prepareSale( BOL_BillingSale $sale )
    {
        // ... gateway custom manipulations

        return $this->billingService->saveSale($sale);
    }

    public function verifySale( BOL_BillingSale $sale )
    {
        // ... gateway custom manipulations

        return $this->billingService->saveSale($sale);
    }

    public function getFields( $params = null )
    {
        // call event to get subaccount config
        $event = new OW_Event('billingccbill.get-subaccount-config', array('pluginKey' => $params['pluginKey'], 'entityKey' => $params['entityKey']));
        OW::getEventManager()->trigger($event);
        $data = $event->getData();
        $subaccount = !empty($data) ? $data : $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'clientSubacc');

        return array(
            'formActionUrl' => self::FORM_ACTION_URL,
            'clientAccnum' => $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'clientAccnum'),
            'clientSubacc' => $subaccount,
            'formName' => $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, $params['formType'] . 'FormName'),
        );
    }

    public function getOrderFormUrl()
    {
        return OW::getRouter()->urlForRoute('billing_ccbill_select_type');
    }

    public function getLogoUrl()
    {
        $plugin = OW::getPluginManager()->getPlugin('billingccbill');

        return $plugin->getStaticUrl() . 'img/ccbill_logo.png';
    }

    public static function getCurrencies()
    {
        $result = array();

        foreach ( self::$currencies as $cur => $code )
        {
            $result[$cur] = $cur;
        }

        return $result;
    }

    /**
     * Returns active currency corresponding code
     * 
     * @return int
     */
    public function getActiveCurrencyCode()
    {
        $currency = $this->billingService->getActiveCurrency();

        if ( mb_strlen($currency) && key_exists($currency, self::$currencies) )
        {
            return self::$currencies[$currency];
        }

        return null;
    }

    /**
     * Generates digest key for single transaction
     * 
     * @param float $formPrice
     * @param int $formPeriod
     * @param int $currencyCode
     * @return string
     */
    public function generateSingleTransactionDigest( $formPrice, $formPeriod, $currencyCode )
    {
        $salt = $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'dynamicPricingSalt');

        return md5($formPrice . $formPeriod . $currencyCode . $salt);
    }

    /**
     * Generates digest key for recurring transaction
     * 
     * @param float $formPrice
     * @param int $formPeriod
     * @param float $formRecurringPrice
     * @param int $formRecurringPeriod
     * @param int $formRebills
     * @param int $currencyCode
     * @return string
     */
    public function generateRecurringTransactionDigest( $formPrice, $formPeriod, $formRecurringPrice, $formRecurringPeriod, $formRebills, $currencyCode )
    {
        $salt = $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'dynamicPricingSalt');

        return md5($formPrice . $formPeriod . $formRecurringPrice . $formRecurringPeriod . $formRebills . $currencyCode . $salt);
    }

    /**
     * Checks if transaction was approved
     * 
     * @param $clientAccnum
     * @param $clientSubacc
     * @param $transId
     * @param $digest
     * @return boolean
     */
    public function transactionApproved( $clientAccnum, $clientSubacc, $transId, $digest )
    {
        if ( $clientAccnum != $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'clientAccnum') )
        {
            return false;
        }

        $salt = $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'dynamicPricingSalt');

        $calcDigest = md5($transId . 1 . $salt);

        return strcmp($calcDigest, $digest) == 0;
    }

    /**
     * Parses DataLink response string
     * 
     * @param string $string
     * @return array
     */
    public function parseDatalinkResponse( $string )
    {
        $return = array();

        $rows = explode("\n", $string);

        if ( !count($rows) )
        {
            return $return;
        }
        
        foreach ( $rows as $id => $row )
        {
            $row = '",' . $row . ',"';
            $exploded = explode('","', $row);
            
            unset($exploded[0]);
            array_pop($exploded);

            if ( empty($exploded[1]) )
            {
                return $return;
            }
            
            $format = $this->getDataLinkFieldsFormat($exploded[1]);
            
            foreach ( $exploded as $fieldId => $fieldValue )
            {
                $return[$id][$format[$fieldId - 1]] = $fieldValue;    
            }
        }

        return $return;
    }

    /**
     * Returns datetime in CCBill date format
     * 
     * @param $time
     * @return string
     */
    public function getDateFormat( $time )
    {
        return date("Y", $time) . date("m", $time) . date("d", $time) . '010101';
    }

    /**
     * Returns DataLink fields for different transaction types
     * 
     * @param string $transactionType
     * @return array
     */
    public function getDataLinkFieldsFormat( $transactionType )
    {
        switch ( $transactionType )
        {
            case 'NEW':
                $format = array(
                    'transaction_type', 'account_number', 'subaccount_number', 'subscription_id', 'timestamp',
                    'first_name', 'last_name', 'username', 'password', 'address', 'city', 'state', 'postal_code',
                    'country', 'email', 'partner_id', 'subscription_status', 'initial_amount', 'initial_period', 
                    'recurring_amount', 'recurring_period', 'recurring_status', 'card_type', 'billing_terms_type', 
                    'billing_contract_id'
                );
                break;

            case 'REBILL':
                $format = array(
                    'transaction_type', 'account_number', 'subaccount_number', 'subscription_id', 'timestamp',
                    'rebill_transaction_id', 'amount', 'billing_terms_type', 'billing_contract_id'
                );
                break;

            case 'EXPIRE':
                $format = array(
                    'transaction_type', 'account_number', 'subaccount_number', 'subscription_id', 'expire_date',
                    'cancel_date', 'batched_transaction'
                );
                break;
        }

        return $format;
    }

    /**
     * Call Data Link Service by cron
     * 
     */
    public function runDataLinkService()
    {
        $tranTypes = array('NEW', 'REBILL');
        $response = $this->getDataLinkServiceResponse($tranTypes);

        if ( !count($response) )
        {
            return false;
        }

        $billingService = BOL_BillingService::getInstance();
        $adapter = new BILLINGCCBILL_CLASS_CcbillAdapter();

        foreach ( $response as $bill )
        {
            switch ( $bill['transaction_type'] )
            {
                case 'NEW':
                    $hash = $bill['username'] . $bill['password'];

                    $sale = $billingService->getSaleByHash($hash);

                    if ( $sale && $sale->status != BOL_BillingSaleDao::STATUS_DELIVERED )
                    {
                        $sale->transactionUid = $bill['subscription_id'];

                        if ( $billingService->verifySale($adapter, $sale) )
                        {
                            $productAdapter = $billingService->getProductAdapter($sale->entityKey);

                            if ( $productAdapter )
                            {
                                $billingService->deliverSale($productAdapter, $sale);
                            }
                        }
                    }
                    break;

                case 'REBILL':
                    $subscriptionId = $bill['subscription_id'];
                    $rebillTransId = $bill['rebill_transaction_id'];
                    $gatewayKey = BILLINGCCBILL_CLASS_CcbillAdapter::GATEWAY_KEY;
                    $gateway = $billingService->findGatewayByKey($gatewayKey);

                    $sale = $billingService->getSaleByGatewayTransactionId($gatewayKey, $subscriptionId);

                    if ( $sale )
                    {
                        if ( $billingService->saleDelivered($rebillTransId, $gateway->id) )
                        {
                            break;
                        }
                        
                        $rebillSaleId = $billingService->registerRebillSale($adapter, $sale, $rebillTransId);

                        if ( $rebillSaleId )
                        {
                            $rebillSale = $billingService->getSaleById($rebillSaleId);

                            $productAdapter = $billingService->getProductAdapter($rebillSale->entityKey);
                            
                            if ( $productAdapter )
                            {
                                $billingService->deliverSale($productAdapter, $rebillSale);
                            }
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Sends request to DataLink Service
     * 
     * @param array $transactionTypes
     * @param boolean $testMode
     * @return array
     */
    public function getDataLinkServiceResponse( array $transactionTypes, $testMode = false )
    {        
        $requestStr = self::DATALINK_URL .
            '?startTime=' . $this->getDateFormat(time() - 24 * 60 * 60) .
            '&endTime=' . $this->getDateFormat(time()) .
            '&transactionTypes=' . implode(',', $transactionTypes) .
            '&clientAccnum=' . $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'clientAccnum') .
            '&clientSubacc=' . $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'clientSubacc') .
            '&username=' . $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'datalinkUsername') .
            '&password=' . $this->billingService->getGatewayConfigValue(self::GATEWAY_KEY, 'datalinkPassword') .
            ($testMode ? '&testMode=1' : '');

        $logger = OW::getLogger('billingccbill');
        $logger->addEntry($requestStr, 'datalink.request-string');

        $handle = curl_init($requestStr);
        ob_start();
        curl_exec($handle);
        $string = ob_get_contents();
        ob_end_clean();

        $logger->addEntry($string, 'datalink.response-string');
        
        $responseArr = array();

        if ( !curl_errno($handle) )
        {
            $responseArr = $this->parseDatalinkResponse($string);
            $logger->addEntry(print_r($responseArr, true), 'datalink.response-array');
        }

        curl_close($handle);
        
        $logger->writeLog();

        return $responseArr;
    }

    public function getAdditionalSubaccounts()
    {
        // collect additional subaccount configs
        $event = new BASE_CLASS_EventCollector('billingccbill.collect-subaccount-fields', array());
        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        if ( !is_array($data) )
        {
            return null;
        }

        $billingService = BOL_BillingService::getInstance();
        $subaccounts = array();
        foreach ( $data as $sub )
        {
            if ( isset($sub['key']) && isset($sub['label']) )
            {
                $subaccounts[$sub['key']] = array(
                    'label' => $sub['label'],
                    'value' => $billingService->getGatewayConfigValue(self::GATEWAY_KEY, $sub['key'])
                );
            }
        }

        return $subaccounts;
    }
}