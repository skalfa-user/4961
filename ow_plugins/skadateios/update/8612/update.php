<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_system_plugins.skadateios.update
 * @since 1.0
 */

$config = Updater::getConfigService();

if ( !$config->configExists('skadateios', 'itunes_secret') )
{
    $config->addConfig('skadateios', 'itunes_secret', '', 'Itunes shared secret');
}

if ( !$config->configExists('skadateios', 'itunes_mode') )
{
    $config->addConfig('skadateios', 'itunes_mode', 'test', 'Itunes mode');
}

OW::getPluginManager()->addPluginSettingsRouteName('skadateios', 'skadateios.admin_settings');

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'skaddateios');
