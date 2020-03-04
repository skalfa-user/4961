<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

$config = Updater::getConfigService();

if ( !$config->configExists('skadateios', 'app_url') )
{
    $config->addConfig('skadateios', 'app_url', 'https://itunes.apple.com/us/app/dating-app/id872986237?ls=1&mt=8');
}

if ( !$config->configExists('skadateios', 'smart_banner') )
{
    $config->addConfig('skadateios', 'smart_banner', true);
}

Updater::getLanguageService()->importPrefixFromZip(__DIR__ . DS . 'langs.zip', 'skaddateios');
