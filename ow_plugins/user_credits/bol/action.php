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
 * Data Transfer Object for `usercredits_action` table.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.user_credits.bol
 * @since 1.0
 */
class USERCREDITS_BOL_Action extends OW_Entity
{
    /**
     * @var string
     */
    public $pluginKey;
    /**
     * @var string
     */
    public $actionKey;
    /**
     * @var int
     */
    public $isHidden = 0;
    /**
     * @var string
     */
    public $settingsRoute;
    /**
     * @var int
     */
    public $active = 1;
}