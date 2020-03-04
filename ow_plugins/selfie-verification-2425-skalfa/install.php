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


$pluginKey = 'photver';

OW::getPluginManager()->addPluginSettingsRouteName($pluginKey, 'admin_photver_settings');

$path = OW::getPluginManager()->getPlugin($pluginKey)->getRootDir() . 'langs.zip';
OW::getLanguage()->importPluginLangs($path, $pluginKey);

$dbo = OW::getDbo();

$addColumnIsVerifiedQuery = 'ALTER TABLE `'.OW_DB_PREFIX.'base_user` ADD `isVerified` TINYINT(1) NOT NULL DEFAULT "0"';
$createTableVerificationQuery = '
CREATE TABLE `' . OW_DB_PREFIX . $pluginKey . '_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `photoHash` varchar(50) DEFAULT NULL,
  `updateStamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
';

try
{
    $dbo->query($addColumnIsVerifiedQuery);
    $dbo->query($createTableVerificationQuery);
}
catch( Exception $e )
{
    OW::getLogger()->addEntry($e->getTraceAsString(), 'plugin_install_error');
}

try
{
    OW::getDbo()->query('CREATE TABLE `' . OW_DB_PREFIX . $pluginKey . '_reasons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
    `user_id` INT UNSIGNED NOT NULL ,
    `reason_text` VARCHAR(300) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
    PRIMARY KEY (`id`)) ENGINE = InnoDB;');
}
catch ( Exception $e )
{
    OW::getLogger()->addEntry($e->getTraceAsString(), 'plugin_install_error');
}


