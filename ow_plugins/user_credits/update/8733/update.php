<?php

/**
 * Copyright (c) 2012, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
if ( !Updater::getConfigService()->configExists('usercredits', 'allow_grant_credits') )
{
    Updater::getConfigService()->addConfig('usercredits', 'allow_grant_credits', "1");
}

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'usercredits');