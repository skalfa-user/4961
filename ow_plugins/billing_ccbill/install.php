<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
$billingService = BOL_BillingService::getInstance();

$gateway = new BOL_BillingGateway();
$gateway->gatewayKey = 'billingccbill';
$gateway->adapterClassName = 'BILLINGCCBILL_CLASS_CcbillAdapter';
$gateway->active = 0;
$gateway->mobile = 0;
$gateway->recurring = 1;
$gateway->currencies = 'AUD,CAD,EUR,GBP,JPY,USD';

$billingService->addGateway($gateway);


$billingService->addConfig('billingccbill', 'clientAccnum', '');
$billingService->addConfig('billingccbill', 'clientSubacc', '0000');
$billingService->addConfig('billingccbill', 'clientSubaccCredits', '');
$billingService->addConfig('billingccbill', 'ccFormName', '');
$billingService->addConfig('billingccbill', 'ckFormName', '');
$billingService->addConfig('billingccbill', 'dpFormName', '');
$billingService->addConfig('billingccbill', 'edFormName', '');
$billingService->addConfig('billingccbill', 'dynamicPricingSalt', '');
$billingService->addConfig('billingccbill', 'datalinkUsername', '');
$billingService->addConfig('billingccbill', 'datalinkPassword', '');


OW::getPluginManager()->addPluginSettingsRouteName('billingccbill', 'billing_ccbill_admin');

$path = OW::getPluginManager()->getPlugin('billingccbill')->getRootDir() . 'langs.zip';
OW::getLanguage()->importPluginLangs($path, 'billingccbill');