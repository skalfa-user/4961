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

$billingService->deleteConfig('billingccbill', 'clientAccnum');
$billingService->deleteConfig('billingccbill', 'clientSubacc');
$billingService->deleteConfig('billingccbill', 'ccFormName');
$billingService->deleteConfig('billingccbill', 'ckFormName');
$billingService->deleteConfig('billingccbill', 'dynamicPricingSalt');
$billingService->deleteConfig('billingccbill', 'datalinkUsername');
$billingService->deleteConfig('billingccbill', 'datalinkPassword');

$billingService->deleteGateway('billingccbill');