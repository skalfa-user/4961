<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
$activeTypes = json_decode(Updater::getConfigService()->getValue("moderation", "content_types"), true);
$activeTypes["user_join"] = (bool)Updater::getConfigService()->getValue("base", "mandatory_user_approve");

Updater::getConfigService()->saveConfig("moderation", "content_types", json_encode($activeTypes));