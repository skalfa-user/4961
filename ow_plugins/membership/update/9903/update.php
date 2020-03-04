<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
try {
    Updater::getDbo()->query(" ALTER TABLE `".OW_DB_PREFIX."membership_plan` ADD COLUMN `periodUnits` varchar(20) NOT NULL default 'days' ");
} catch (Exception $ex) {
    
}

Updater::getLanguageService()->deleteLangKey('membership', 'plan_struct_trial');
Updater::getLanguageService()->deleteLangKey('membership', 'plan_struct_recurring');
Updater::getLanguageService()->deleteLangKey('membership', 'plan_struct');
Updater::getLanguageService()->deleteLangKey('membership', 'trial_granted');


Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'membership');
