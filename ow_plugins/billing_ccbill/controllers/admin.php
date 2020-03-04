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
 * CCBill admin controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.billing_ccbill.controllers
 * @since 1.0
 */
class BILLINGCCBILL_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function index()
    {
        $billingService = BOL_BillingService::getInstance();
        $language = OW::getLanguage();

        $ccbillConfigForm = new CcbillConfigForm();
        $this->addForm($ccbillConfigForm);

        if ( OW::getRequest()->isPost() && $ccbillConfigForm->isValid($_POST) )
        {
            $ccbillConfigForm->process();
            OW::getFeedback()->info($language->text('billingccbill', 'settings_updated'));
            $this->redirect();
        }

        $adapter = new BILLINGCCBILL_CLASS_CcbillAdapter();
        $this->assign('logoUrl', $adapter->getLogoUrl());

        $gateway = $billingService->findGatewayByKey(BILLINGCCBILL_CLASS_CcbillAdapter::GATEWAY_KEY);
        $this->assign('gateway', $gateway);

        $this->assign('activeCurrency', $billingService->getActiveCurrency());

        $supported = $billingService->currencyIsSupported($gateway->currencies);
        $this->assign('currSupported', $supported);

        $subAccounts = $adapter->getAdditionalSubaccounts();
        $this->assign('subAccounts', $subAccounts);

        $this->setPageHeading(OW::getLanguage()->text('billingccbill', 'config_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_app');
    }
}

class CcbillConfigForm extends Form
{
    public function __construct()
    {
        parent::__construct('ccbill-config-form');

        $language = OW::getLanguage();
        $billingService = BOL_BillingService::getInstance();
        $gwKey = BILLINGCCBILL_CLASS_CcbillAdapter::GATEWAY_KEY;

        $clientAccnum = new TextField('clientAccnum');
        $clientAccnum->setValue($billingService->getGatewayConfigValue($gwKey, 'clientAccnum'));
        $this->addElement($clientAccnum);

        $clientSubacc = new TextField('clientSubacc');
        $clientSubacc->setValue($billingService->getGatewayConfigValue($gwKey, 'clientSubacc'));
        $this->addElement($clientSubacc);

        $adapter = new BILLINGCCBILL_CLASS_CcbillAdapter();
        $subAccounts = $adapter->getAdditionalSubaccounts();

        if ( $subAccounts )
        {
            foreach ( $subAccounts as $key => $sub )
            {
                $field = new TextField($key);
                $field->setLabel($sub['label']);
                $field->setValue($sub['value']);
                $this->addElement($field);
            }
        }

        $ccFormName = new TextField('ccFormName');
        $ccFormName->setValue($billingService->getGatewayConfigValue($gwKey, 'ccFormName'));
        $this->addElement($ccFormName);

        $ckFormName = new TextField('ckFormName');
        $ckFormName->setValue($billingService->getGatewayConfigValue($gwKey, 'ckFormName'));
        $this->addElement($ckFormName);
        
        $dpFormName = new TextField('dpFormName');
        $dpFormName->setValue($billingService->getGatewayConfigValue($gwKey, 'dpFormName'));
        $this->addElement($dpFormName);

        $edFormName = new TextField('edFormName');
        $edFormName->setValue($billingService->getGatewayConfigValue($gwKey, 'edFormName'));
        $this->addElement($edFormName);
        
        $dynamicPricingSalt = new TextField('dynamicPricingSalt');
        $dynamicPricingSalt->setValue($billingService->getGatewayConfigValue($gwKey, 'dynamicPricingSalt'));
        $this->addElement($dynamicPricingSalt);

        $datalinkUsername = new TextField('datalinkUsername');
        $datalinkUsername->setValue($billingService->getGatewayConfigValue($gwKey, 'datalinkUsername'));
        $this->addElement($datalinkUsername);

        $datalinkPassword = new PasswordField('datalinkPassword');
        $datalinkPassword->setValue($billingService->getGatewayConfigValue($gwKey, 'datalinkPassword'));
        $this->addElement($datalinkPassword);

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('billingccbill', 'btn_save'));
        $this->addElement($submit);
    }

    public function process()
    {
        $values = $this->getValues();

        $billingService = BOL_BillingService::getInstance();
        $gwKey = BILLINGCCBILL_CLASS_CcbillAdapter::GATEWAY_KEY;

        $billingService->setGatewayConfigValue($gwKey, 'clientAccnum', $values['clientAccnum']);
        $billingService->setGatewayConfigValue($gwKey, 'clientSubacc', $values['clientSubacc']);
        $billingService->setGatewayConfigValue($gwKey, 'ccFormName', $values['ccFormName']);
        $billingService->setGatewayConfigValue($gwKey, 'ckFormName', $values['ckFormName']);
        $billingService->setGatewayConfigValue($gwKey, 'dpFormName', $values['dpFormName']);
        $billingService->setGatewayConfigValue($gwKey, 'edFormName', $values['edFormName']);
        $billingService->setGatewayConfigValue($gwKey, 'dynamicPricingSalt', $values['dynamicPricingSalt']);
        $billingService->setGatewayConfigValue($gwKey, 'datalinkUsername', $values['datalinkUsername']);
        $billingService->setGatewayConfigValue($gwKey, 'datalinkPassword', $values['datalinkPassword']);

        // update additional sub-account values
        $adapter = new BILLINGCCBILL_CLASS_CcbillAdapter();
        $subAccounts = $adapter->getAdditionalSubaccounts();

        if ( $subAccounts )
        {
            foreach ( $subAccounts as $key => $sub )
            {
                if ( array_key_exists($key, $values) )
                {
                    $billingService->setGatewayConfigValue($gwKey, $key, $values[$key]);
                }
            }
        }
    }
}