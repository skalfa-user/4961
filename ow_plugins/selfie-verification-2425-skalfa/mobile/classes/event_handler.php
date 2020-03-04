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

/**
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow.ow_plugins.photver.classes
 * @since 1.8.4
 */
class PHOTVER_MCLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var PHOTVER_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * @var PHOTVER_BOL_Service
     */
    protected $service;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return PHOTVER_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function init()
    {
        $this->service = PHOTVER_BOL_Service::getInstance();

        $eventManager = OW::getEventManager();
        $eventManager->bind('class.get_instance.SKADATE_MCTRL_Join', array($this, 'onSKADATE_CTRL_JoinInstance'));
        $eventManager->bind(OW_EventManager::ON_AFTER_ROUTE, array($this, 'onAfterRoute'));
    }

    public function onSKADATE_CTRL_JoinInstance( OW_Event $event )
    {
        $event->setData(new PHOTVER_MCTRL_Join());
    }

    /**
     * @param OW_Event $event
     */
    public function onAfterRoute( OW_Event $event )
    {
        if( OW::getUser()->isAdmin() || !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $userId = OW::getUser()->getId();

        $isUserVerified = $this->service->isUserVerified(OW::getUser()->getId());

        if( !$isUserVerified )
        {
            $isUserUploadPhoto = $this->service->haveUserPassedSecondVerificationStep($userId);
            $signOutDispatchAttrs = OW::getRouter()->getRoute('base_sign_out')->getDispatchAttrs();

            if( $isUserUploadPhoto )
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('base.wait_for_approval', array('controller' => 'BASE_MCTRL_WaitForApproval', 'action' => 'index'));
                OW::getRequestHandler()->addCatchAllRequestsExclude('base.wait_for_approval', $signOutDispatchAttrs['controller'], $signOutDispatchAttrs['action']);
                OW::getRequestHandler()->addCatchAllRequestsExclude('base.wait_for_approval', 'BASE_MCTRL_AjaxLoader', 'component');
                OW::getRequestHandler()->addCatchAllRequestsExclude('base.wait_for_approval', 'BASE_MCTRL_Invitations', 'command');
                OW::getRequestHandler()->addCatchAllRequestsExclude('base.wait_for_approval', 'BASE_MCTRL_Ping', 'index');

            }
            else
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('photver.upload_photo', array('controller' => 'PHOTVER_MCTRL_UploadController', 'action' => 'uploadPhoto'));
                OW::getRequestHandler()->addCatchAllRequestsExclude('photver.upload_photo', $signOutDispatchAttrs['controller'], $signOutDispatchAttrs['action']);
                OW::getRequestHandler()->addCatchAllRequestsExclude('photver.upload_photo', 'BASE_MCTRL_AjaxLoader', 'component');
                OW::getRequestHandler()->addCatchAllRequestsExclude('photver.upload_photo', 'BASE_MCTRL_Invitations', 'command');
                OW::getRequestHandler()->addCatchAllRequestsExclude('photver.upload_photo', 'BASE_MCTRL_Ping', 'index');
            }
        }
    }

}
