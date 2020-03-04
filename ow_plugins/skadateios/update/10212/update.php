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
        Updater::getDbo()->query($q);
    } catch (Exception $ex) {
        // Log
    }
}

Updater::getLanguageService()->importPrefixFromZip(__DIR__ . DS . 'langs.zip', 'skadateios');

if ( !Updater::getConfigService()->configExists('skadateios', 'push_mode') )
{
    Updater::getConfigService()->addConfig('skadateios', 'push_mode', 'test');
}

if ( !Updater::getConfigService()->configExists('skadateios', 'push_pass_phrase') )
{
    Updater::getConfigService()->addConfig('skadateios', 'push_pass_phrase', '');
}

if ( !Updater::getConfigService()->configExists('skadateios', 'push_enabled') )
{
    Updater::getConfigService()->addConfig('skadateios', 'push_enabled', '0');
}