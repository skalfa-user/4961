<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

Updater::getLanguageService()->importPrefixFromZip(__DIR__ . DS . 'langs.zip', 'skadateios');

if ( !Updater::getConfigService()->configExists('skadateios', 'flurry_api_key') )
{
    Updater::getConfigService()->addConfig('skadateios', 'flurry_api_key', '');
}