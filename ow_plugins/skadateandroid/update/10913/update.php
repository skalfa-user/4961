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

if ( !Updater::getConfigService()->configExists('skandroid', 'service_account_private_key') )
{
    Updater::getConfigService()->addConfig('skandroid', 'service_account_private_key', '');
}

if ( !Updater::getConfigService()->configExists('skandroid', 'service_account_id') )
{
    Updater::getConfigService()->addConfig('skandroid', 'service_account_id', '');
}

if ( !Updater::getConfigService()->configExists('skandroid', 'service_account_auth_token') )
{
    Updater::getConfigService()->addConfig('skandroid', 'service_account_auth_token', '');
}

if ( !Updater::getConfigService()->configExists('skandroid', 'service_account_auth_expiration_time') )
{
    Updater::getConfigService()->addConfig('skandroid', 'service_account_auth_expiration_time', '');
}

Updater::getLanguageService()->importPrefixFromZip(__DIR__ . DS . 'langs.zip', 'skandroid');