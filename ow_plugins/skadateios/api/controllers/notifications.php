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
 * @package ow_system_plugins.skadateios.api.controllers
 * @since 1.0
 */
class SKADATEIOS_ACTRL_Notifications extends OW_ApiActionController
{
    public function registerDevice( $params )
    {
        $token = $params["token"];
        $lang = $params["lang"];

        $userId = OW::getUser()->getId();

        SKADATEIOS_BOL_PushService::getInstance()->registerDevice($token, $userId, array(
            SKADATEIOS_BOL_PushService::PROPERTY_LANG => $lang
        ));
    }
}