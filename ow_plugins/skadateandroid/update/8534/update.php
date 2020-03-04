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

/**
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.skandroid.update
 * @since 1.0
 */

$config = Updater::getConfigService();

if ( !$config->configExists('skandroid', 'public_key') )
{
    $config->addConfig('skandroid', 'public_key', '', 'Application public key');
}

try
{
    $billingService = BOL_BillingService::getInstance();

    $gateway = new BOL_BillingGateway();
    $gateway->gatewayKey = 'skadateandroid';
    $gateway->adapterClassName = 'SKANDROID_ACLASS_InAppPurchaseAdapter';
    $gateway->active = 0;
    $gateway->mobile = 1;
    $gateway->recurring = 1;
    $gateway->dynamic = 0;
    $gateway->hidden = 1;
    $gateway->currencies = 'AUD,CAD,EUR,GBP,JPY,USD';

    $billingService->addGateway($gateway);
}
catch ( Exception $ex )
{
    
}

try
{
    OW::getPluginManager()->addPluginSettingsRouteName('skandroid', 'skandroid_admin_settings');
}
catch ( Exception $ex )
{
    
}

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'skandroid');
