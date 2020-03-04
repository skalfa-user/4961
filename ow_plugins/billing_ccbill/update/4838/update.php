<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
$sql = "SELECT `id` FROM `".OW_DB_PREFIX."base_billing_gateway` WHERE `gatewayKey` = 'billingccbill';";

try {
    $id = Updater::getDbo()->queryForColumn($sql);
    
    if ( $id )
    {
        $sql = "INSERT INTO `".OW_DB_PREFIX."base_billing_gateway_config` 
            SET `gatewayId` = :id, `name` = :name, `value` = '';";
        
        try {
            Updater::getDbo()->insert($sql, array('gatewayId' => $id, 'name' => 'dpFormName'));
        }
        catch ( Exception $e ){ $exArr[] = $e; }
        
        try {
            Updater::getDbo()->insert($sql, array('gatewayId' => $id, 'name' => 'edFormName'));
        }
        catch ( Exception $e ){ $exArr[] = $e; }
    }
}
catch ( Exception $e ){ $exArr[] = $e; }


Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'billingccbill');