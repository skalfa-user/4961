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

require_once OW::getPluginManager()->getPlugin("skandroid")->getRootDir() . "vendor" . DS . "autoload.php";

OW::getRouter()->addRoute( new OW_Route('skandroid_admin_settings', 'admin/plugin/skandroid/settings', 'SKANDROID_CTRL_Settings', 'index') );
OW::getRouter()->addRoute( new OW_Route('skandroid_admin_analytics', 'admin/plugin/skandroid/analytics', 'SKANDROID_CTRL_Settings', 'analytics') );
OW::getRouter()->addRoute( new OW_Route('skandroid_admin_ads', 'admin/plugin/skandroid/ads', 'SKANDROID_CTRL_Settings', 'ads') );
OW::getRouter()->addRoute( new OW_Route('skandroid.admin_push', 'admin/plugin/skandroid/push', 'SKANDROID_CTRL_Settings', 'pushNotifications') );

SKANDROID_CLASS_EventHandler::getInstance()->init();

//SKANDROID_BOL_PushService::getInstance()->sendNotifiation(1, array("key" => "sss"), array("type" => "sardar"));
