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
 * CCBill order pages controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.billing_ccbill.controllers
 * @since 1.0
 */
class BILLINGCCBILL_CTRL_Order extends OW_ActionController
{

    public function select()
    {
        if ( isset($_POST['type']) && in_array($_POST['type'], array('cc', 'ck', 'dp', 'ed')) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('billing_ccbill_order_form', array('type' => $_POST['type'])));
        }
        
        $lang = OW::getLanguage();
        $gwKey = BILLINGCCBILL_CLASS_CcbillAdapter::GATEWAY_KEY;
        $gatewayTitle = $lang->text($gwKey, 'gateway_title');
        
        $billingService = BOL_BillingService::getInstance();
        $this->assign('cc', $billingService->getGatewayConfigValue($gwKey, 'ccFormName'));
        $this->assign('ck', $billingService->getGatewayConfigValue($gwKey, 'ckFormName'));
        $this->assign('dp', $billingService->getGatewayConfigValue($gwKey, 'dpFormName'));
        $this->assign('ed', $billingService->getGatewayConfigValue($gwKey, 'edFormName'));
        
        $this->setPageHeading($lang->text('base', 'billing_order_page_heading', array('gateway' => $gatewayTitle)));
        $this->setPageHeadingIconClass('ow_ic_cart');
    }

    public function form( array $params )
    {
        $billingService = BOL_BillingService::getInstance();
        $adapter = new BILLINGCCBILL_CLASS_CcbillAdapter();
        $lang = OW::getLanguage();

        $sale = $billingService->getSessionSale();

        if ( !$sale )
        {
            $url = $billingService->getSessionBackUrl();
            if ( $url != null )
            {
                OW::getFeedback()->warning($lang->text('base', 'billing_order_canceled'));
                $billingService->unsetSessionBackUrl();
                $this->redirect($url);
            }
            else 
            {
                $this->redirectToAction('select');
            }
        }
        
        $formId = uniqid('order_form-');
        $this->assign('formId', $formId);

        $js = '$("#' . $formId . '").submit()';
        OW::getDocument()->addOnloadScript($js);

        $fieldsParams = array(
            'formType' => isset($params['type']) ? $params['type'] : 'cc',
            'pluginKey' => $sale->pluginKey,
            'entityKey' => $sale->entityKey
        );

        $fields = $adapter->getFields($fieldsParams);
        $this->assign('fields', $fields);

        if ( $billingService->prepareSale($adapter, $sale) )
        {
            $sale->totalAmount = sprintf("%01.2f", $sale->totalAmount);
            $sale->price = sprintf("%01.2f", $sale->price);
            
            if ( $sale->recurring )
            {
                $rebills = 99;
                $digest = $adapter->generateRecurringTransactionDigest(
                        $sale->totalAmount, $sale->period, $sale->totalAmount, $sale->period, $rebills, $adapter->getActiveCurrencyCode()
                );
                $this->assign('rebills', $rebills);
            }
            else
            {
                $digest = $adapter->generateSingleTransactionDigest($sale->totalAmount, $sale->period, $adapter->getActiveCurrencyCode());
            }

            $this->assign('formDigest', $digest);
            $this->assign('currencyCode', $adapter->getActiveCurrencyCode());
            $this->assign('sale', $sale);

            $masterPageFileDir = OW::getThemeManager()->getMasterPageTemplate('blank');
            OW::getDocument()->getMasterPage()->setTemplate($masterPageFileDir);

            $billingService->unsetSessionSale();
        }
        else
        {
            $productAdapter = $billingService->getProductAdapter($sale->entityKey);

            if ( $productAdapter )
            {
                $productUrl = $productAdapter->getProductOrderUrl();
            }
            
            OW::getFeedback()->warning($lang->text('base', 'billing_order_init_failed'));
            $url = isset($productUrl) ? $productUrl : $billingService->getOrderFailedPageUrl();
            
            $this->redirect($url);
        }
    }

    public function postback()
    {
        $logger = OW::getLogger('billingccbill');
        $logger->addEntry(print_r($_POST, true), 'postback.data-array');
        $logger->writeLog();

        $clientAccnum = $_POST['clientAccnum'];
        $clientSubacc = $_POST['clientSubacc'];
        $amount = $_POST['initialPrice'] ? $_POST['initialPrice'] : $_POST['recurringPrice'];
        $saleHash = $_POST['custom'];
        $transId = $_POST['subscription_id'];
        $digest = $_POST['responseDigest'];

        if ( !mb_strlen($saleHash) )
        {
            $logger->addEntry('Missed sale hash', 'postback.data-array');
            $logger->writeLog();
            exit;
        }

        if ( !mb_strlen($transId) )
        {
            $logger->addEntry('Missed transaction id', 'postback.data-array');
            $logger->writeLog();
            exit;
        }

        $billingService = BOL_BillingService::getInstance();
        $adapter = new BILLINGCCBILL_CLASS_CcbillAdapter();

        $sale = $billingService->getSaleByHash($saleHash);

        if ( !$sale )
        {
            $logger->addEntry('Empty sale object', 'postback.data-array');
            $logger->writeLog();
            exit;
        }

        if ( $amount != $sale->totalAmount )
        {
            $logger->addEntry("Wrong amount: " . $amount , 'postback.amount-mismatch');
            $logger->writeLog();
            exit;
        }

        if ( $billingService->getGatewayConfigValue(BILLINGCCBILL_CLASS_CcbillAdapter::GATEWAY_KEY, 'clientAccnum') != $clientAccnum )
        {
            $logger->addEntry("Wrong CCBill account: " . $clientAccnum , 'postback.account-mismatch');
            $logger->writeLog();
            exit;
        }

        if ( $adapter->transactionApproved($clientAccnum, $clientSubacc, $transId, $digest) )
        {
            if ( $sale->status != BOL_BillingSaleDao::STATUS_DELIVERED )
            {
                $sale->transactionUid = $transId;

                if ( $billingService->verifySale($adapter, $sale) )
                {
                    $sale = $billingService->getSaleById($sale->id);

                    $productAdapter = $billingService->getProductAdapter($sale->entityKey);

                    if ( $productAdapter )
                    {
                        $billingService->deliverSale($productAdapter, $sale);
                    }
                    else
                    {
                        $logger->addEntry('Empty product adapter object', 'postback.data-array');
                        $logger->writeLog();
                    }
                }
                else
                {
                    $logger->addEntry('Verify sale problem', 'postback.data-array');
                    $logger->writeLog();
                }
            }
            else
            {
                $logger->addEntry('Not delivered status', 'postback.data-array');
                $logger->writeLog();
            }
        }
        else
        {
            $logger->addEntry('Transaction not approved', 'postback.data-array');
            $logger->writeLog();
        }

        exit;
    }
}