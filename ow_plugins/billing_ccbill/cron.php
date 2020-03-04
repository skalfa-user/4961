<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
class BILLINGCCBILL_Cron extends OW_Cron
{
    const DATALINK_SERVICE_RUN_INTERVAL = 60;

    public function __construct()
    {
        parent::__construct();

        $this->addJob('runDataLinkServiceProcess', self::DATALINK_SERVICE_RUN_INTERVAL);
    }

    public function run()
    {
        
    }

    public function runDataLinkServiceProcess()
    {
        $adapter = new BILLINGCCBILL_CLASS_CcbillAdapter();
        $adapter->runDataLinkService();
    }
}