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
  ADD UNIQUE KEY `deviceToken` (`deviceToken`(64)),
  ADD KEY `userId` (`userId`)",
    "ALTER TABLE `{$dbPrefix}skandroid_device`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT"
);

foreach ( $sql as $q )
{
    try
    {
        Updater::getDbo()->query($q);
    }
    catch ( Exception $ex )
    {
        Updater::getLogger()->addEntry($ex->getTraceAsString(), "plugin_update_error");
    }
}

if ( !Updater::getConfigService()->configExists("skandroid", "push_enabled") )
{
    Updater::getConfigService()->addConfig("skandroid", "push_enabled", "0");
}

if ( !Updater::getConfigService()->configExists("skandroid", "gmc_api_key") )
{
    Updater::getConfigService()->addConfig("skandroid", "gmc_api_key", "");
}

Updater::getLanguageService()->importPrefixFromZip(__DIR__ . DS . 'langs.zip', 'skadateandroid');
