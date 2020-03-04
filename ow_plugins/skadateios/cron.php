<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 * 
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 * 
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
class SKADATEIOS_Cron extends OW_Cron
{

    public function __construct()
    {
        parent::__construct();

        $this->addJob('removeInvalidDevices', 24 * 60);
    }

    public function run()
    {
        
    }

    public function removeInvalidDevices()
    {
        $invalidTokens = SKADATEIOS_BOL_PushService::getInstance()->getInvalidDeviceTokens();
        SKADATEIOS_BOL_PushService::getInstance()->unregisterDevices($invalidTokens);
    }
}