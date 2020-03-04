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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_core
 * @since 1.0
 */
class SKANDROID_ACLASS_EventHandler
{
    /*     * ************************************************************************* */
    const EVENT_COLLECT_AUTHORIZATION_ACTIONS = 'skandroid.collect_auth_actions';
    const PING_EVENT = 'skandroid.base.ping';
    const USER_LIST_PREPARE_USER_DATA = 'skandroid.user_list_prepare_user_data';

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

    public function onCollectVirtualGiftsAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'virtualgifts' => array(
                    'label' => $language->text('virtualgifts', 'auth_group_label'),
                    'actions' => array(
                        'send_gift' => $language->text('virtualgifts', 'auth_action_label_send_gift')
                    )
                )
            )
        );
    }

    public function onCollectHotListAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'hotlist' => array(
                    'label' => $language->text('hotlist', 'auth_group_label'),
                    'actions' => array(
                        'add_to_list' => $language->text('hotlist', 'auth_action_label_add_to_list')
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
                OW::getRequestHandler()->setCatchAllRequestsAttributes('skandroid.suspended', array(
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKANDROID_ACTRL_Base',
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'suspended'
                ));
            } 
            else if ( !$user->emailVerify && OW::getConfig()->getValue('base', 'confirm_email') )
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('skandroid.not_verified', array(
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKANDROID_ACTRL_Base',
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'notVerified'
                ));
            }
            else if ( OW::getConfig()->getValue('base', 'mandatory_user_approve') && !$userService->isApproved($user->id) )
            {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('skandroid.not_approved', array(
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKANDROID_ACTRL_Base',
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'notApproved'
                ));
            }
            else if( OW::getConfig()->getValue("base", "maintenance") ){
                OW::getRequestHandler()->setCatchAllRequestsAttributes('skandroid.maintenance', array(
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKANDROID_ACTRL_Base',
                    OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'maintenance'
                ));
            }
        }
        else
        {
            OW::getRequestHandler()->setCatchAllRequestsAttributes('skandroid.not_authenticated', array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'SKANDROID_ACTRL_Base',
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

        $service = SKANDROID_ABOL_Service::getInstance();
        $menu = $service->getMenu(OW::getUser()->getId());
        $counter = $service->getNewItemsCount($menu);
        $data = array('menu' => $menu, 'counter' => $counter);

        $eventData = $event->getData();

        if ( empty($eventData) )
        {
            $data = array('menu' => $menu, 'counter' => $counter);
        }
        else if ( is_array($eventData) )
        {
            $data = array_merge($eventData, $data);
        }

        $event->setData($data);

        return $data;
    }

    public function onRenderWinkInMailbox( OW_Event $event )
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

        if ( $params['winkBackEnabled'] && $wink->getPartnerId() == OW::getUser()->getId() )
        {
            $data['winkBackEnabled'] = true;
        }
        else
        {
            $data['winkBackEnabled'] = false;
        }

        $data['isWinkedBack'] = $wink->getWinkback();
        if ( $data['isWinkedBack'] )
        {
            if ( $wink->getUserId() == OW::getUser()->getId() )
            {
                $data['text'] = BOL_UserService::getInstance()->getDisplayName($wink->getPartnerId()) . ' ' . OW::getLanguage()->text('winks', 'wink_back_message_owner');
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

    public function onRenderWinkBackInMailbox( OW_Event $event )
    {
        $params = $event->getParams();

        $data = array();

        $data['eventName'] = 'renderWinkBack';

        if ( empty($params['winkId']) || ($wink = WINKS_BOL_Service::getInstance()->findWinkById($params['winkId'])) === NULL )
        {
            $data['text'] = '';
        }
        else
        {
            if ( $wink->getUserId() == OW::getUser()->getId() )
            {
                $data['text'] = BOL_UserService::getInstance()->getDisplayName($wink->getPartnerId()) . ' ' . OW::getLanguage()->text('winks', 'wink_back_message_owner');
            }
            else
            {
                $data['text'] = OW::getLanguage()->text('winks', 'winked_back_msg');
            }
        }

        $data['winkBackEnabled'] = false;

        $event->setData($data);
    }

    public function onRenderOembedInMailbox( OW_Event $event )
    {
        $params = $event->getParams();
        $content = $params['href'];
        $event->setData($content);
    }

    public function onPing( OW_Event $event )
    {
        $eventParams = $event->getParams();

        if ( empty($eventParams['commands']) )
        {
            return;
        }

        $commands = json_decode($eventParams["commands"], true);
        $commandsResult = array();

        foreach ( $commands as $name => $params )
        {
            $pingEvent = new OW_Event(self::PING_EVENT . '.' . trim($name), !empty($params) ? json_decode($params, true) : array());
            OW::getEventManager()->trigger($pingEvent);

            $commandsResult[$name] = $pingEvent->getData();
        }

        $event->setData($commandsResult);
    }

    public function onMailboxGetChatNewMessages( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !OW::getUser()->isAuthenticated() || empty($params['mode']) )
        {
            return;
        }

        $userId = OW::getUser()->getId();
        $lastMessageTimestamp = empty($params['lastMessageTimestamp']) ? 0 : $params['lastMessageTimestamp'];

        switch ( $params['mode'] )
        {
            case 'chat':
                if ( empty($params['opponentId']) )
                {
                    return;
                }

                $opponentId = $params['opponentId'];
                $conversationId = OW::getEventManager()->call('mailbox.get_conversation_id', array(
                    'userId' => $userId,
                    'opponentId' => $opponentId
                ));
                $list = SKANDROID_ABOL_MailboxService::getInstance()->getNewMessages($userId, $opponentId, $lastMessageTimestamp);
                break;
            case 'mail':
            default:
                if ( empty($params['conversationId']) )
                {
                    return;
                }

                $conversationId = $params['conversationId'];
                $list = SKANDROID_ABOL_MailboxService::getInstance()->getNewMessagesForConversation($conversationId, $lastMessageTimestamp);
                break;
        }

        $ignoreList = $idList = array();

        foreach( $list as $message )
        {
            if ( $message['convId'] == $conversationId )
            {
                $ignoreList[] = $message['id'];
            }

            if ( !$message['isAuthor'] && $message['readMessageAuthorized'] )
            {
                $idList[] = $message['id'];
            }
        }

        if ( !empty($idList) )
        {
            MAILBOX_BOL_ConversationService::getInstance()->markMessageIdListRead($idList);
        }

        $event->setData(array(
            'list' => $list,
            'countUnread' => SKANDROID_ABOL_MailboxService::getInstance()->countUnreadMessage($conversationId, $userId),
            'count' => OW::getEventManager()->call('mailbox.get_unread_message_count', array(
                'userId' => $userId,
                'ignoreList' => $ignoreList
            ))
        ));
    }

    public function onMailboxGetNewMessages( OW_Event $event )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $params = $event->getParams();
        $userId = OW::getUser()->getId();
        $ignoreMessageList = !empty($params['ignoreMessageList']) ? $params['ignoreMessageList'] : array();
        $lastRequestTimestamp = !empty($params['lastRequestTimestamp']) ? (int)$params['lastRequestTimestamp'] : 0;
        $activeModes = OW::getEventManager()->call('mailbox.get_active_mode_list');
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        $messages = $conversationService->findUnreadMessagesForApi($userId, $ignoreMessageList, $lastRequestTimestamp, $activeModes);

        if ( !empty($messages) )
        {
            $conversationIdList = array();

            foreach ( $messages as $message )
            {
                if ( !in_array($message['convId'], $conversationIdList) )
                {
                    $conversationIdList[] = $message['convId'];
                }
            }

            $conversations = $conversationService->getChatUserList($userId, 0, count($conversationIdList));

            $event->setData(array(
                'messages' => SKANDROID_ABOL_MailboxService::getInstance()->prepareMessageList($messages),
                'list' => SKANDROID_ABOL_MailboxService::getInstance()->prepareConversationList($conversations)
            ));
        }
    }

    public function onWinkRequestPingNotifications( OW_Event $event )
    {
        if ( OW::getPluginManager()->isPluginActive('winks') )
        {
            $event->setData(SKADATEIOS_ABOL_Service::getInstance()->getWinkRequests());
        }
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
        $em->bind(self::EVENT_COLLECT_AUTHORIZATION_ACTIONS, array($this, 'onCollectVirtualGiftsAuthLabels'));
        $em->bind(self::EVENT_COLLECT_AUTHORIZATION_ACTIONS, array($this, 'onCollectHotListAuthLabels'));

        $em->bind('skandroid.base.ping', array($this, 'onPing'));
        $em->bind(self::PING_EVENT . '.mailbox_dialog', array($this, 'onMailboxGetChatNewMessages'));
        $em->bind(self::PING_EVENT . '.mailbox_new_messages', array($this, 'onMailboxGetNewMessages'));
        $em->bind(self::PING_EVENT . '.wink_requests', array($this, 'onWinkRequestPingNotifications'));
        $em->bind(OW_EventManager::ON_AFTER_ROUTE, array($this, "onAfterRoute"));

//        $em->bind('mailbox.renderOembed', array($this, 'onRenderOembedInMailbox'));
//        $em->bind('wink.renderWink', array($this, 'onRenderWinkInMailbox'));
//        $em->bind('wink.renderWinkBack', array($this, 'onRenderWinkBackInMailbox'));
    }
}
