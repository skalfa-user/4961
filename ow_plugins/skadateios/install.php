<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

$sql = array();
$sql[] = "CREATE TABLE `" . OW_DB_PREFIX . "skadateios_device` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `deviceToken` varchar(64) NOT NULL,
  `properties` text NOT NULL,
  `timeStamp` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$sql[] = "ALTER TABLE `" . OW_DB_PREFIX . "skadateios_device`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Device Token` (`deviceToken`),
  ADD KEY `User Id` (`userId`);";

$sql[] = "ALTER TABLE `" . OW_DB_PREFIX . "skadateios_device` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

foreach ( $sql as $q )
{
    try {
        OW::getDbo()->query($q);
    } catch (Exception $ex) {
        // Log
    }
}

$config = OW::getConfig();

if ( !$config->configExists('skadateios', 'billing_enabled') )
{
    $config->addConfig('skadateios', 'billing_enabled', 0, 'Billing enabled');
}

if ( !$config->configExists('skadateios', 'itunes_secret') )
{
    $config->addConfig('skadateios', 'itunes_secret', '', 'Itunes shared secret');
}

if ( !$config->configExists('skadateios', 'itunes_mode') )
{
    $config->addConfig('skadateios', 'itunes_mode', 'test', 'Itunes mode');
}

if ( !$config->configExists('skadateios', 'app_url') )
{
    $config->addConfig('skadateios', 'app_url', 'https://itunes.apple.com/us/app/dating-app/id872986237?ls=1&mt=8');
}

if ( !$config->configExists('skadateios', 'smart_banner') )
{
    $config->addConfig('skadateios', 'smart_banner', true);
}

if ( !$config->configExists("skadateios", 'flurry_api_key') )
{
    $config->addConfig("skadateios", 'flurry_api_key', '');
}

if ( !$config->configExists("skadateios", 'admod_api_key') )
{
    $config->addConfig("skadateios", 'admod_api_key', '');
}

if ( !$config->configExists('skadateios', 'push_mode') )
{
    $config->addConfig('skadateios', 'push_mode', 'test');
}

if ( !$config->configExists('skadateios', 'push_pass_phrase') )
{
    $config->addConfig('skadateios', 'push_pass_phrase', '');
}

if ( !$config->configExists('skadateios', 'push_enabled') )
{
    $config->addConfig('skadateios', 'push_enabled', '0');
}

OW::getPluginManager()->addPluginSettingsRouteName('skadateios', 'skadateios.admin_settings');

$billingService = BOL_BillingService::getInstance();

$gateway = new BOL_BillingGateway();
$gateway->gatewayKey = 'skadateios';
$gateway->adapterClassName = 'SKADATEIOS_ACLASS_InAppPurchaseAdapter';
$gateway->active = 0;
$gateway->mobile = 1;
$gateway->recurring = 1;
$gateway->dynamic = 0;
$gateway->hidden = 1;
$gateway->currencies = 'AUD,CAD,EUR,GBP,JPY,USD';

$billingService->addGateway($gateway);

OW::getLanguage()->importPluginLangs(OW::getPluginManager()->getPlugin('skadateios')->getRootDir() . 'langs.zip', 'skadateios');