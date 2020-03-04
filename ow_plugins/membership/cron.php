<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * Membership cron job.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.membership.bol
 * @since 1.0
 */
class MEMBERSHIP_Cron extends OW_Cron
{
    const MEMBERSHIP_EXPIRE_JOB_RUN_INTERVAL = 60;

    const MEMBERSHIP_EXPIRE_NOTIFICATIONS_LIMIT = 10;

    public function __construct()
    {
        parent::__construct();

        $this->addJob('membershipExpireProcess', self::MEMBERSHIP_EXPIRE_JOB_RUN_INTERVAL);
    }

    public function run()
    {
        
    }

    public function membershipExpireProcess()
    {
        MEMBERSHIP_BOL_MembershipService::getInstance()->expireUsersMemberships();
        MEMBERSHIP_BOL_MembershipService::getInstance()->sendExpirationNotifications(self::MEMBERSHIP_EXPIRE_NOTIFICATIONS_LIMIT);
    }
}