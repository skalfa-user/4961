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

Updater::getLanguageService()->deleteLangKey('skandroid', 'ads_desc');
Updater::getLanguageService()->importPrefixFromZip(__DIR__ . DS . 'langs.zip', 'skandroid');

if ( !Updater::getConfigService()->configExists('skandroid', 'analytics_api_key') )
{
    Updater::getConfigService()->addConfig('skandroid', 'analytics_api_key', '');
}

if ( !Updater::getConfigService()->configExists('skandroid', 'ads_api_key') )
{
    Updater::getConfigService()->addConfig('skandroid', 'ads_api_key', '');
}
