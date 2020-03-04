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

$config = Updater::getConfigService();

if ( !$config->configExists('skandroid', 'app_url') )
{
    $config->addConfig('skandroid', 'app_url', 'https://play.google.com/store/apps/details?id=com.skadatexapp&hl=en');
}

if ( !$config->configExists('skandroid', 'smart_banner') )
{
    $config->addConfig('skandroid', 'smart_banner', true);
}

Updater::getLanguageService()->importPrefixFromZip(__DIR__ . DS . 'langs.zip', 'skandroid');
