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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.usercredits.classes
 * @since 1.0
 */
class USERCREDITS_CLASS_BaseCredits
{
    private $actions;
    
    public function __construct()
    {
        $this->actions[] = array('pluginKey' => 'base', 'action' => 'daily_login', 'amount' => 0);
        $this->actions[] = array('pluginKey' => 'base', 'action' => 'user_join', 'amount' => 0);
        $this->actions[] = array('pluginKey' => 'base', 'action' => 'search_users', 'amount' => 0);
        $this->actions[] = array('pluginKey' => 'base', 'action' => 'add_comment', 'amount' => 0);
        $this->actions[] = array('pluginKey' => 'usercredits', 'hidden' => true, 'action' => 'buy_credits', 'amount' => 0);
        $this->actions[] = array('pluginKey' => 'usercredits', 'hidden' => true, 'action' => 'grant_by_user', 'amount' => 0);
        $this->actions[] = array('pluginKey' => 'usercredits', 'hidden' => true, 'action' => 'grant_to_user', 'amount' => 0);
        $this->actions[] = array('pluginKey' => 'usercredits', 'hidden' => true, 'action' => 'set_by_admin', 'amount' => 0);
    }
    
    public function bindCreditActionsCollect( BASE_CLASS_EventCollector $e )
    {
        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }
    }
    
    public function triggerCreditActionsAdd()
    {
        $e = new BASE_CLASS_EventCollector('usercredits.action_add');
        
        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }

        OW::getEventManager()->trigger($e);
    }
}