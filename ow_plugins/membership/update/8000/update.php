<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
$config = Updater::getConfigService();
if ( !$config->configExists('membership', 'notify_period') )
{
    $config->addConfig('membership', 'notify_period', '3', 'Remind users by email that membership expires in days');
}

$sql = "ALTER TABLE  `".OW_DB_PREFIX."membership_user` ADD `expirationNotified` TINYINT NOT NULL DEFAULT '0';";

try
{
    Updater::getDbo()->query($sql);
}
catch ( Exception $e ) { }

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'membership');