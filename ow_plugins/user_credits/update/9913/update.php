<?php

/**
 * Copyright (c) 2012, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
$sql = "ALTER TABLE `".OW_DB_PREFIX."usercredits_log` ADD `groupKey` VARCHAR( 255 ) NULL DEFAULT NULL ;";
try {
    Updater::getDbo()->query($sql);
}
catch ( Exception $ex )
{

}