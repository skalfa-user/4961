<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
$sql = "ALTER TABLE `".OW_DB_PREFIX."usercredits_action` ADD `disabled` tinyint(1) NOT NULL DEFAULT 0;";

try
{
    Updater::getDbo()->query($sql);
}
catch ( Exception $e )
{
    $exArr[] = $e;
}

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'usercredits');