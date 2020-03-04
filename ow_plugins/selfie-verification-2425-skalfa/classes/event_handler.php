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
class PHOTVER_CLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var PHOTVER_CLASS_EventHandler
     */
    private static $classInstance;

    /**
     * @var PHOTVER_BOL_Service
     */
    protected $service;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return PHOTVER_CLASS_EventHandler
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
        $eventManager = OW::getEventManager();
        $eventManager->bind('class.get_instance.SKADATE_CTRL_Join', array($this, 'onSKADATE_CTRL_JoinInstance'));

        //$eventManager->bind(OW_EventManager::ON_AFTER_ROUTE, array($this, 'onAfterRoute')); // uncomment if you are using desktop version also
        $this->genericInit();
    }

    public function genericInit()
    {
        $this->service = PHOTVER_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(OW_EventManager::ON_USER_REGISTER, array($this, 'onUserRegister'));
        $eventManager->bind('skmobileapp.formatted_users_data', [$this, 'onGettingFormattedUsersData']);
    }

    public function onGettingFormattedUsersData( OW_Event $event )
    {
        $data = $event->getData();

        if ( !empty($data) ) {
            $photoVerService = PHOTVER_BOL_Service::getInstance();
            $status = PHOTVER_BOL_Service::PHOTO_IS_VERIFIED;

            foreach ( $data as $key => $dataUser )
            {
                $userId = $dataUser['id'];

                $loginUserId = null;

                if ( OW::getUser() ) {
                    $loginUserId = OW::getUser()->getId();
                }

                if ( $loginUserId && $userId == $loginUserId ) {
                    $isUserVerified = $photoVerService->isUserVerified($loginUserId);

                    if( !$isUserVerified )
                    {
                        $isUserUploadPhoto = $photoVerService->haveUserPassedSecondVerificationStep($loginUserId);

                        if( $isUserUploadPhoto )
                        {
                            $status = PHOTVER_BOL_Service::PENDING_APPROVAL;

                        }
                        else
                        {
                            $status = PHOTVER_BOL_Service::PHOTO_NOT_UPLOAD;
                        }
                    }

                    $data[$key]['statusPhoto'] = $status;
                } else if ( !$loginUserId ) {
                    $isUserVerified = $photoVerService->isUserVerified($userId);

                    if( !$isUserVerified )
                    {
                        $isUserUploadPhoto = $photoVerService->haveUserPassedSecondVerificationStep($userId);

                        if( $isUserUploadPhoto )
                        {
                            $status = PHOTVER_BOL_Service::PENDING_APPROVAL;

                        }
                        else
                        {
                            $status = PHOTVER_BOL_Service::PHOTO_NOT_UPLOAD;
                        }
                    }

                    $data[$key]['statusPhoto'] = $status;
                }
//                $data[$key]['statusPhoto'] = 1;
            }

            $event->setData($data);
        }
    }

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

            if( $isUserUploadPhoto )
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('base.wait_for_approval', array('controller' => 'BASE_CTRL_WaitForApproval', 'action' => 'index'));
                OW::getRequestHandler()->addCatchAllRequestsExclude('base.wait_for_approval', 'BASE_CTRL_User', 'signOut');

            }
            else
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('photver.upload_photo', array('controller' => 'PHOTVER_CTRL_UploadController', 'action' => 'uploadPhoto'));
                OW::getRequestHandler()->addCatchAllRequestsExclude('photver.upload_photo', 'BASE_CTRL_User', 'signOut');
            }
        }
    }

    public function onSKADATE_CTRL_JoinInstance( OW_Event $event )
    {
        $event->setData(new PHOTVER_CTRL_Join());
    }

    public function onUserRegister()
    {
        $userId = OW::getUser()->getId();

        if ( isset($_FILES[PHOTVER_BOL_Service::PHOTO_VERIFICATION_CONTROL_NAME]) )
        {
            $this->service->markVerificationPhotoStep( $userId, $_FILES[PHOTVER_BOL_Service::PHOTO_VERIFICATION_CONTROL_NAME]['tmp_name'] );
        }

        $this->service->deleteDeclineReason($userId);
    }

}
