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
 * @author Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_system_plugins.skandroid.api.controllers
 * @since 1.0
 */
class SKANDROID_ABOL_MailboxService
{
    const MODE_MAIL = 1;
    const MODE_CHAT = 2;

    const ACTION_SEND_CHAT_MESSAGE = 'send_chat_message';
    const ACTION_READ_CHAT_MESSAGE = 'read_chat_message';
    const ACTION_REPLY_CHAT_MESSAGE = 'reply_to_chat_message';

    const ACTION_SEND_MESSAGE = 'send_message';
    const ACTION_READ_MESSAGE = 'read_message';
    const ACTION_REPLY_TO_MESSAGE = 'reply_to_message';

    private static $classInstance;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function prepareConversationList( array $list )
    {
        if ( empty($list) )
        {
            return array();
        }

        $result = $userList = $userIdList = array();
        $currentDate = getdate();
        $language = OW::getLanguage();
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

//        foreach ( $list as $conversation )
//        {
//            $userIdList[] = $conversation['opponentId'];
//        }
//
//        foreach ( BOL_UserService::getInstance()->findUserListByIdList($userIdList) as $user )
//        {
//            $userList[$user->id] = $user;
//        }

        foreach ( $list as $conversation )
        {
            $_conversation = $conversation;

            $date = getdate($conversation['lastMessageTimestamp']);

            if ( $date['yday'] == $currentDate['yday'] )
            {
                $_conversation['date'] = strftime('%H:%M', $conversation['lastMessageTimestamp']);
            }
            else
            {
                $_conversation['date'] = $language->text('base', 'date_time_month_short_' . $date['mon']) . ' ' .  $date['mday'];
            }

            $_conversation['isOnline'] = !empty($conversation['onlineStatus']);
            $_conversation['mode'] = $conversation['mode'] == 'chat' ? self::MODE_CHAT : self::MODE_MAIL;
            $_conversation['conversationRead'] = !empty($conversation['conversationRead']);
            $_conversation['isDeleted'] = !isset($userList[$conversation['opponentId']]);

            if ( is_array($conversation['previewText']) )
            {
                $_conversation['previewText'] = strip_tags($conversation['previewText']['text']);
            }
            else
            {
                $_conversation['previewText'] = strip_tags($conversation['previewText']);
            }

            $message = $conversationService->getLastMessage($conversation['conversationId']);

            if ( $message->isSystem )
            {
                $json = json_decode($message->text, true);
                $_conversation['wink'] = !empty($json['entityType']) && $json['entityType'] == 'wink';
            }

            $result[] = $_conversation;
        }

        return $result;

    }

    public function prepareMessageList( array $list )
    {
        if ( empty($list) )
        {
            return array();
        }

        $result = array();

        foreach ( $list as $message )
        {
            $_message = $message;
            $_message['mode'] = $message['mode'] == 'chat' ? self::MODE_CHAT : self::MODE_MAIL;
            $_message['recipientRead'] = !empty($message['recipientRead']);
            $_message['isSystem'] = !empty($message['isSystem']) && is_array($message['text']);

            if ( $_message['isSystem'] )
            {
                if ( !empty($message['text']['eventName']) )
                {
                    if ( OW::getPluginManager()->isPluginActive('winks') )
                    {
                        switch ($message['text']['eventName'])
                        {
                            case 'authorizationPromoted':
                                $_message['isSystem'] = false;
                                $_message['text'] = strip_tags($message['text']['text']);
                                break;
                            case 'renderWink':
                                if ( $message['senderId'] == OW::getUser()->getId() )
                                {
                                    $_message['text'] = strip_tags(OW::getLanguage()->text('winks', 'accept_wink_msg'));
                                }
                                else
                                {
                                    $_message['text'] = BOL_UserService::getInstance()->getDisplayName($message['senderId']) . ' ' . OW::getLanguage()->text('winks', 'wink_back_message');
                                }

                                $tmpMessage = MAILBOX_BOL_ConversationService::getInstance()->getMessage($message['id']);
                                $json = json_decode($tmpMessage->text, true);

                                if ( !empty($json['params']['winkBackEnabled']) )
                                {
                                    $_message['systemType'] = $message['text']['eventName'];
                                }
                                else
                                {
                                    $_message['systemType'] = 'winkIsSent';
                                }
                                break;
                            case 'renderWinkBack':
                                if ( $message['senderId'] == OW::getUser()->getId() )
                                {
                                    $_message['text'] = strip_tags(OW::getLanguage()->text('winks', 'winked_back_msg'));
                                }
                                else
                                {
                                    $_message['text'] = BOL_UserService::getInstance()->getDisplayName($message['senderId']) . ' ' . OW::getLanguage()->text('winks', 'wink_back_message_owner');
                                }

                                $tmpMessage = MAILBOX_BOL_ConversationService::getInstance()->getMessage($message['id']);
                                $json = json_decode($tmpMessage->text, true);

                                if ( !empty($json['params']['winkBackEnabled']) )
                                {
                                    $_message['systemType'] = $message['text']['eventName'];
                                }
                                break;
                            default:
                                $_message['text'] = strip_tags($message['text']['text']);
                                break;
                        }
                    }
                    else
                    {
                        $_message['text'] = strip_tags($message['text']['text']);
                    }
                }
                elseif ( !empty($message['text']['text']) )
                {
                    $_message['systemType'] = 'simple';
                    $_message['text'] = strip_tags($message['text']['text']);
                }
            }
            else
            {
                $_message['text'] = strip_tags(BOL_TextFormatService::getInstance()->processWsForOutput($message['text'], array('buttons' => array(
                    BOL_TextFormatService::WS_BTN_BOLD,
                    BOL_TextFormatService::WS_BTN_ITALIC
                ))), '<b><i><br><br/><p>');
            }

            //            $_message['senderAvatarUrl'] = strcasecmp($message['senderAvatarUrl'], $defaultAvatar) === 0 ? null : $message['senderAvatarUrl'];
            //            $_message['recipientAvatarUrl'] = strcasecmp($message['recipientAvatarUrl'], $defaultAvatar) === 0 ? null : $message['recipientAvatarUrl'];
            $result[] = $_message;
        }

        return $result;
    }

    public function getNewMessages( $userId, $opponentId, $lastMessageTimestamp )
    {
        $list = OW::getEventManager()->call('mailbox.get_new_messages', array(
            'userId' => $userId,
            'opponentId' => $opponentId,
            'lastMessageTimestamp' => $lastMessageTimestamp
        ));

        return $this->prepareMessageList($list);
    }

    public function getNewMessagesForConversation( $conversationId, $lastMessageTimestamp )
    {
        $list = OW::getEventManager()->call('mailbox.get_new_messages_for_conversation', array(
            'conversationId' => $conversationId,
            'lastMessageTimestamp' => $lastMessageTimestamp
        ));

        return $this->prepareMessageList($list);
    }

    public function getBillingInfo( array $actionNames )
    {
        $result = array(
            'billingEnabled' => SKANDROID_ABOL_Service::getInstance()->isBillingEnabled(),
            'subscribeEnable' => OW::getPluginManager()->isPluginActive('membership'),
            'creditsEnable' => OW::getPluginManager()->isPluginActive('usercredits')
        );

        if ( count($actionNames) !== 0 )
        {
            $result['authorization'] = $this->getAuthorizeInfo($actionNames);
        }

        return $result;
    }

    public function getAuthorizeInfo( array $actionNames )
    {
        if ( count($actionNames) === 0 )
        {
            return array();
        }

        $service = SKANDROID_ABOL_Service::getInstance();
        $info = array();

        foreach ( $actionNames as $actionName )
        {
            $info[] = array(
                'actionName' => $actionName,
                'status' => $service->getAuthorizationActionStatus('mailbox', $actionName)
            );
        }

        return $info;
    }

    public function markConversationAsRead( $userId, array $conversationIdList )
    {
        if ( empty($userId) || empty($conversationIdList) )
        {
            return null;
        }

        $result = OW::getEventManager()->call('mailbox.mark_unread', array(
            'userId' => $userId,
            'conversationId' => $conversationIdList
        ));

        if ( $result )
        {
            $messageIdList = array();

            foreach ( $conversationIdList as $conversationId )
            {
                $count = MAILBOX_BOL_MessageDao::getInstance()->getConversationLength($conversationId);

                if ( $count )
                {
                    $messages = MAILBOX_BOL_MessageDao::getInstance()->findListByConversationId($conversationId, (int)$count);

                    foreach ( $messages as $message )
                    {
                        $messageIdList[] = $message->id;
                    }
                }
            }

            if ( !empty($messageIdList) )
            {
                MAILBOX_BOL_ConversationService::getInstance()->markMessageIdListRead($messageIdList);
            }
        }

        return $result;
    }

    public function sendWinkNotification( $userId, $partnerId )
    {
        if ( empty($userId) || empty($partnerId) ||
            ($user = BOL_UserService::getInstance()->findUserById($userId)) === null ||
            ($partner = BOL_UserService::getInstance()->findUserById($partnerId)) === null )
        {
            return;
        }

        $avatarUrls = BOL_AvatarService::getInstance()->getAvatarsUrlList(array($userId, $partnerId));
        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList(array($userId, $partnerId));
        $subjectKey = 'wink_back_email_subject';
        $subjectArr = array('displayname' => $displayNames[$userId]);

        $textContentKey = 'wink_back_email_text_content';
        $htmlContentKey = 'wink_back_email_html_content';
        $contentArr = array(
            'src' => $avatarUrls[$userId],
            'displayname' => $displayNames[$userId],
            'url' => OW_URL_HOME . 'user/' . $user->getUsername(),
            'conversation_url' => OW_URL_HOME . 'messages'
        );

        $language = OW::getLanguage();
        $mail = OW::getMailer()->createMail();

        $mail->addRecipientEmail($partner->getEmail());
        $mail->setSubject($language->text('winks', $subjectKey, $subjectArr));
        $mail->setTextContent($language->text('winks', $textContentKey, $contentArr));
        $mail->setHtmlContent($language->text('winks', $htmlContentKey, $contentArr));

        try
        {
            OW::getMailer()->send($mail);
        }
        catch ( Exception $e )
        {
            OW::getLogger('wink.send_notify')->addEntry(json_encode($e));
        }
    }

    public function countUnreadMessage( $conversationId, $userId )
    {
        if ( empty($conversationId) || empty($userId) )
        {
            return 0;
        }

        $unreadMessagesCount = MAILBOX_BOL_ConversationService::getInstance()->countUnreadMessagesForConversationList(array($conversationId), $userId);
        return !empty($unreadMessagesCount[$conversationId]) ? $unreadMessagesCount[$conversationId] : 0;
    }
}
