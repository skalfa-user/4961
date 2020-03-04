<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

require_once __DIR__ . DS . "vendor" . DS . "autoload.php";

OW::getRouter()->addRoute( new OW_Route('skadateios.admin_settings', 'admin/plugin/skadateios/settings', 'SKADATEIOS_CTRL_Settings', 'index') );
OW::getRouter()->addRoute( new OW_Route('skadateios.admin_analytics', 'admin/plugin/skadateios/analytics', 'SKADATEIOS_CTRL_Settings', 'analytics') );
OW::getRouter()->addRoute( new OW_Route('skadateios.admin_ads', 'admin/plugin/skadateios/ads', 'SKADATEIOS_CTRL_Settings', 'ads') );
OW::getRouter()->addRoute( new OW_Route('skadateios.admin_push', 'admin/plugin/skadateios/push-notifications', 'SKADATEIOS_CTRL_Settings', 'pushNotifications') );

SKADATEIOS_CLASS_EventHandler::getInstance()->init();