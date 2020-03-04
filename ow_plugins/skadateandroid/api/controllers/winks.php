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

class SKANDROID_ACTRL_Winks extends OW_ApiActionController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function sendWink( $post )
    {
        $viewerId = OW::getUser()->getId();

        if ( !$viewerId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($post['userId']) )
        {
            throw new ApiResponseErrorException();
        }

        $userId = (int) $post['userId'];

        $service = WINKS_BOL_Service::getInstance();

        if ( $service->sendWink($viewerId, $userId) )
        {
            OW::getEventManager()->trigger(new OW_Event('winks.send_wink',
                array('userId' => $viewerId, 'partnerId' => $userId)));
        }
    }

    public function sendWinkBack( $params )
    {
        $partnerId = OW::getUser()->getId();

        if ( !$partnerId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['userId']) )
        {
            throw new ApiResponseErrorException();
        }

        $userId = (int) $params['userId'];

        $service = WINKS_BOL_Service::getInstance();

        $wink = $service->findWinkByUserIdAndPartnerId($userId, $partnerId);

        if ( empty($wink) )
        {
            throw new ApiResponseErrorException();
        }

        $service->setWinkback($wink->getId(), TRUE);

        $event = new OW_Event('winks.onWinkBack',
            array(
            'userId' => $wink->getUserId(),
            'partnerId' => $wink->getPartnerId(),
            'conversationId' => $wink->getConversationId(),
            'content' => array(
                'entityType' => 'wink',
                'eventName' => 'renderWinkBack',
                'params' => array(
                    'winkId' => $wink->id,
                    'messageId' => $params['messageId']
                )
            )
        ));
        OW::getEventManager()->trigger($event);
    }

    public function acceptWink( $params )
    {
        if ( empty($params['winkId']) || !OW::getPluginManager()->isPluginActive("winks") )
        {
            throw new ApiResponseErrorException();
        }

        $service = WINKS_BOL_Service::getInstance();

        /**
         * @var $wink WINKS_BOL_Winks
         */
        $wink = $service->findWinkById(-$params["winkId"]);

        if ( empty($wink) )
        {
            throw new ApiResponseErrorException();
        }

        $wink->setStatus(WINKS_BOL_WinksDao::STATUS_ACCEPT);
        WINKS_BOL_WinksDao::getInstance()->save($wink);

        if ( ($_wink = $service->findWinkByUserIdAndPartnerId($wink->getPartnerId(), $wink->getUserId())) !== NULL )
        {
            $_wink->setStatus(WINKS_BOL_WinksDao::STATUS_IGNORE);
            WINKS_BOL_WinksDao::getInstance()->save($_wink);
        }

        $params = array(
            'userId' => $wink->getUserId(),
            'partnerId' => $wink->getPartnerId(),
            'content' => array(
                'entityType' => 'wink',
                'eventName' => 'renderWink',
                'params' => array(
                    'winkId' => $wink->id,
                    'winkBackEnabled' => 1
                )
            )
        );

        $event = new OW_Event('winks.onAcceptWink', $params);
        OW::getEventManager()->trigger($event);

        $data = $event->getData();
        $conversationData = array();

        if ( !empty($data['conversationId']) )
        {
            $wink->setConversationId($data['conversationId']);
            WINKS_BOL_WinksDao::getInstance()->save($wink);

            $conversationItem = MAILBOX_BOL_ConversationService::getInstance()->
                getConversationListByUserId(OW::getUser()->getId(), 0, 1, $data['conversationId']);

            //$conversationData = array_shift($conversationItem);
        }

        $this->assign('cId', $data['conversationId']);
    }

    public function ignoreWink( $params )
    {
        if ( empty($params['winkId']) || !OW::getPluginManager()->isPluginActive("winks") )
        {
            throw new ApiResponseErrorException();
        }


        $service = WINKS_BOL_Service::getInstance();
        $wink = $service->findWinkById(-$params["winkId"]);
        if ( !$wink )
        {
            $this->assign('result', false);
            return;
        }
        $wink->setStatus(WINKS_BOL_WinksDao::STATUS_IGNORE);
        WINKS_BOL_WinksDao::getInstance()->save($wink);
        $event = new OW_Event('winks.onIgnoreWink', array('userId' => $params['userId'], 'partnerId' => $partnerId));
        OW::getEventManager()->trigger($event);
        $this->assign('result', true);
    }

    /**
     * Get wink requests
     */
    public function getWinkRequests()
    {
        $this->assign('result', SKADATEIOS_ABOL_Service::getInstance()->getWinkRequests());
    }
}
