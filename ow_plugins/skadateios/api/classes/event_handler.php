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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_core
 * @since 1.0
 */
class SKADATEIOS_ACLASS_EventHandler
{
    const EVENT_COLLECT_AUTHORIZATION_ACTIONS = 'skadateios.collect_auth_actions';
    const USER_LIST_PREPARE_USER_DATA = 'skadateios.user_list_prepare_user_data';

    public function onCollectBaseAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'base' => array(
                    'label' => $language->text('base', 'auth_group_label'),
                    'actions' => array(
                        'search_users' => $language->text('base', 'search_users'),
                        'view_profile' => $language->text('base', 'auth_view_profile')
                    )
                )
            )
        );
    }

    public function onCollectMailboxAuthLabels( BASE_CLASS_EventCollector $event )
    {
        if ( !OW::getPluginManager()->isPluginActive("mailbox") )
        {
            return;
        }
        
        $language = OW::getLanguage();
        $event->add(
            array(
                'mailbox' => array(
                    'label' => $language->text('mailbox', 'auth_group_label'),
                    'actions' => array(
                        'send_chat_message' => $language->text('mailbox', 'auth_action_label_send_chat_message'),
                        'read_chat_message' => $language->text('mailbox', 'auth_action_label_read_chat_message'),
                        'reply_to_chat_message' => $language->text('mailbox', 'auth_action_label_reply_to_chat_message'),

                        'send_message' => $language->text('mailbox', 'auth_action_label_send_message'),
                        'read_message' => $language->text('mailbox', 'auth_action_label_read_message'),
                        'reply_to_message' => $language->text('mailbox', 'auth_action_label_reply_to_message')
                    )
                )
            )
        );
    }

    public function onCollectPhotoAuthLabels( BASE_CLASS_EventCollector $event )
    {
        if ( !OW::getPluginManager()->isPluginActive("photo") )
        {
            return;
        }
        
        $language = OW::getLanguage();
        $event->add(
            array(
                'photo' => array(
                    'label' => $language->text('photo', 'auth_group_label'),
                    'actions' => array(
                        'upload' => $language->text('photo', 'auth_action_label_upload'),
                        'view' => $language->text('photo', 'auth_action_label_view')
                    )
                )
            )
        );
    }

    public function onCollectHotListAuthLabels( BASE_CLASS_EventCollector $event )
    {
        if ( !OW::getPluginManager()->isPluginActive("hotlist") )
        {
            return;
        }

        $language = OW::getLanguage();
        $event->add(
            array(
                'hotlist' => array(
                    'label' => $language->text('hotlist', 'auth_group_label'),
                    'actions' => array(
                        'add_to_list' => $language->text('hotlist', 'add_to_list')
                    )
                )
            )
        );
    }
    
    public function onAfterRoute()
    {
        $userService = BOL_UserService::getInstance();
        
        if ( OW::getUser()->isAuthenticated() )
        {
            $user = OW::getUser()->getUserObject();
            
            if ( $userService->isSuspended($user->id) )
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('skadateios.suspended', array(
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKADATEIOS_ACTRL_Base',
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'suspended'
                ));
            } 
            else if ( OW::getConfig()->getValue('base', 'mandatory_user_approve') && !$userService->isApproved($user->id) )
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('skadateios.not_approved', array(
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKADATEIOS_ACTRL_Base',
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'notApproved'
                ));
            } 
            else if ( !$user->emailVerify && OW::getConfig()->getValue('base', 'confirm_email') )
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('skadateios.not_verified', array(
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKADATEIOS_ACTRL_Base',
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'notVerified'
                ));
            }
        }
        else
        {
            OW::getRequestHandler()->setCatchAllRequestsAttributes('skadateios.not_authenticated', array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKADATEIOS_ACTRL_Base',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'notAuthenticated'
            ));
        }
    }

    public function onPingNotifications( OW_Event $event )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return null;
        }

        $service = SKADATEIOS_ABOL_Service::getInstance();
        $menu = $service->getMenu(OW::getUser()->getId());
        $counter = $service->getNewItemsCount($menu);
        $data = array('menu' => $menu, 'counter' => $counter);

        $eventData = $event->getData();

        if (empty($eventData))
        {
            $data = array('menu' => $menu, 'counter' => $counter);
        }
        else if (is_array($eventData))
        {
            $data = array_merge($eventData, $data);
        }

        $event->setData($data);

        return $data;
    }

    /**
     * Get list of wink requests
     *
     * @param OW_Event $event
     */
    public function onWinkRequestPingNotifications( OW_Event $event )
    {
        if ( OW::getPluginManager()->isPluginActive('winks') )
        {
            $event->setData(SKADATEIOS_ABOL_Service::getInstance()->getWinkRequests());
        }
    }

    public function onRenderWinkInMailbox(OW_Event $event )
    {
        if ( !OW::getPluginManager()->isPluginActive('winks') )
        {
            return;
        }

        $params = $event->getParams();

        $service = WINKS_BOL_Service::getInstance();

        /**
         * @var WINKS_BOL_Winks $wink
         */
        if ( ($wink = $service->findWinkById($params['winkId'])) === NULL )
        {
            return;
        }

        $data = array();

        $data['eventName'] = 'renderWink';

        if ( $params['winkBackEnabled'] && $wink->getPartnerId() == OW::getUser()->getId())
        {
            $data['winkBackEnabled'] = true;
        }
        else
        {
            $data['winkBackEnabled'] = false;
        }

        $data['isWinkedBack'] = $wink->getWinkback();
        if ($data['isWinkedBack'])
        {
            if ( $wink->getUserId() == OW::getUser()->getId() )
            {
                $data['text'] = BOL_UserService::getInstance()->getDisplayName($wink->getPartnerId()).' '.OW::getLanguage()->text('winks', 'wink_back_message_owner');
            }
            else
            {
                $data['text'] = BOL_UserService::getInstance()->getDisplayName($wink->getUserId()) . ' ' . OW::getLanguage()->text('winks', 'wink_back_message');
                $data['winkBackEnabled'] = true;
            }
        }
        else
        {
            if ( $wink->getUserId() == OW::getUser()->getId() )
            {
                $data['text'] = OW::getLanguage()->text('winks', 'accept_wink_msg');
            }
            else
            {
                $data['text'] = BOL_UserService::getInstance()->getDisplayName($wink->getUserId()) . ' ' . OW::getLanguage()->text('winks', 'wink_back_message');
            }
        }

        $event->setData($data);
    }

    public function onRenderWinkBackInMailbox(OW_Event $event )
    {
        if ( OW::getPluginManager()->isPluginActive('winks') )
        {
            $params = $event->getParams();

            $data = array();

            $data['eventName'] = 'renderWinkBack';

            if (empty($params['winkId']) || ($wink = WINKS_BOL_Service::getInstance()->findWinkById($params['winkId'])) === NULL) {
                $data['text'] = '';
            } else {
                if ($wink->getUserId() == OW::getUser()->getId()) {
                    $data['text'] = BOL_UserService::getInstance()->getDisplayName($wink->getPartnerId()) . ' ' . OW::getLanguage()->text('winks', 'wink_back_message_owner');
                } else {
                    $data['text'] = OW::getLanguage()->text('winks', 'winked_back_msg');
                }
            }

            $data['winkBackEnabled'] = false;

            $event->setData($data);
        }
    }

    public function onRenderOembedInMailbox(OW_Event $event )
    {
        $params = $event->getParams();
        $content = $params['href'];
        $event->setData($content);
    }

    public function init()
    {
        if ( !OW::getRegistry()->get("baseInited") )
        {
            $handler = new BASE_CLASS_EventHandler();
            $handler->genericInit();
            
            OW::getRegistry()->set("baseInited", true);
        }

        $em = OW::getEventManager();

        $em->bind(self::EVENT_COLLECT_AUTHORIZATION_ACTIONS, array($this, 'onCollectBaseAuthLabels'));
        $em->bind(self::EVENT_COLLECT_AUTHORIZATION_ACTIONS, array($this, 'onCollectMailboxAuthLabels'));
        $em->bind(self::EVENT_COLLECT_AUTHORIZATION_ACTIONS, array($this, 'onCollectPhotoAuthLabels'));
        $em->bind(self::EVENT_COLLECT_AUTHORIZATION_ACTIONS, array($this, 'onCollectHotListAuthLabels'));
        $em->bind(SKADATEIOS_ACTRL_Ping::PING_EVENT . '.notifications', array($this, 'onPingNotifications'));
        $em->bind(SKADATEIOS_ACTRL_Ping::PING_EVENT . '.winkRequests', array($this, 'onWinkRequestPingNotifications'));
        $em->bind(OW_EventManager::ON_AFTER_ROUTE, array($this, "onAfterRoute"));

        $em->bind('mailbox.renderOembed', array($this, 'onRenderOembedInMailbox'));
        $em->bind('wink.renderWink', array($this, 'onRenderWinkInMailbox'));
        $em->bind('wink.renderWinkBack', array($this, 'onRenderWinkBackInMailbox'));
    }
}

