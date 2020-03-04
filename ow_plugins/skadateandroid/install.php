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

$config = OW::getConfig();
$pluginKey = "skandroid";

if ( !$config->configExists($pluginKey, 'billing_enabled') )
{
    $config->addConfig($pluginKey, 'billing_enabled', 0, 'Billing enabled');
}

if ( !$config->configExists($pluginKey, 'public_key') )
{
    $config->addConfig($pluginKey, 'public_key', '', 'Application public key');
}

if ( !$config->configExists($pluginKey, 'app_url') )
{
    $config->addConfig($pluginKey, 'app_url', 'https://play.google.com/store/apps/details?id=com.skadatexapp&hl=en');
}

if ( !$config->configExists($pluginKey, 'smart_banner') )
{
    $config->addConfig($pluginKey, 'smart_banner', true);
}


if ( !$config->configExists($pluginKey, 'gmc_api_key') )
{
    $config->addConfig($pluginKey, 'gmc_api_key', "");
}

if ( !$config->configExists($pluginKey, 'push_enabled') )
{
    $config->addConfig($pluginKey, 'push_enabled', "0");
}

if ( !$config->configExists($pluginKey, 'analytics_api_key') )
{
    $config->addConfig($pluginKey, 'analytics_api_key', '');
}

if ( !$config->configExists($pluginKey, 'ads_api_key') )
{
    $config->addConfig($pluginKey, 'ads_api_key', '');
}

if ( !$config->configExists($pluginKey, 'service_account_id') )
{
    $config->addConfig($pluginKey, 'service_account_id', '');
}

if ( !$config->configExists($pluginKey, 'service_account_private_key') )
{
    $config->addConfig($pluginKey, 'service_account_private_key', '');
}

if ( !$config->configExists($pluginKey, 'service_account_auth_token') )
{
    $config->addConfig($pluginKey, 'service_account_auth_token', '');
}

if ( !$config->configExists($pluginKey, 'service_account_auth_expiration_time') )
{
    $config->addConfig($pluginKey, 'service_account_auth_expiration_time', '');
}

if ( !$config->configExists($pluginKey, 'use_firebase') )
{
    $config->addConfig($pluginKey, 'use_firebase', '1');
}

$billingService = BOL_BillingService::getInstance();

$gateway = new BOL_BillingGateway();
$gateway->gatewayKey = $pluginKey;
$gateway->adapterClassName = 'SKANDROID_ACLASS_InAppPurchaseAdapter';
$gateway->active = 0;
$gateway->mobile = 1;
$gateway->recurring = 1;
$gateway->dynamic = 0;
$gateway->hidden = 1;
$gateway->currencies = 'AUD,CAD,EUR,GBP,JPY,USD';

$billingService->addGateway($gateway);

OW::getPluginManager()->addPluginSettingsRouteName('skandroid', 'skandroid_admin_settings');

$dbPrefix = OW_DB_PREFIX;

$sql = array(
    "CREATE TABLE IF NOT EXISTS `{$dbPrefix}skandroid_device` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `deviceToken` text NOT NULL,
  `properties` text NOT NULL,
  `timeStamp` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8",
    "ALTER TABLE `{$dbPrefix}skandroid_device`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deviceToken` (`deviceToken`(64)),
  ADD KEY `userId` (`userId`)",
    "ALTER TABLE `{$dbPrefix}skandroid_device`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT"
);

foreach ( $sql as $q )
{
    try
    {
        OW::getDbo()->query($q);
    }
    catch ( Exception $ex )
    {
        OW::getLogger()->addEntry($ex->getTraceAsString(), "plugin_install_error");
    }
}

OW::getLanguage()->importPluginLangs(OW::getPluginManager()->getPlugin($pluginKey)->getRootDir() . 'langs.zip',
    $pluginKey);
