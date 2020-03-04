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

if ( !Updater::getConfigService()->configExists("skandroid", 'use_firebase') )
{
    Updater::getConfigService()->addConfig("skandroid", 'use_firebase', '0');
}

Updater::getLanguageService()->importPrefixFromDir(__DIR__ . DS . "langs", true);