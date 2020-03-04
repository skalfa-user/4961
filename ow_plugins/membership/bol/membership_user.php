<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * Data Transfer Object for `membership_user` table.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.membership.bol
 * @since 1.0
 */
class MEMBERSHIP_BOL_MembershipUser extends OW_Entity
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $typeId;
    /**
     * @var int
     */
    public $userId;
    /**
     * @var int
     */
    public $expirationStamp;
    /**
     * @var boolean
     */
    public $recurring;
    /**
     * @var int
     */
    public $trial = 0;
    /**
     * @var int
     */
    public $expirationNotified = 0;

    /**
     * @var int
     */
    public $recurringCheckNumber = 0;
}