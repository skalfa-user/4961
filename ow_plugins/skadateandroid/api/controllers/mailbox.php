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
class SKANDROID_ACTRL_Mailbox extends OW_ApiActionController
{
    const CONVERSATION_LIMIT = 10;

    private $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = SKANDROID_ABOL_MailboxService::getInstance();
    }

    public function init()
    {
        parent::init();

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new ApiAccessException(ApiAccessException::TYPE_NOT_AUTHENTICATED);
        }
    }

    public function getModes( $params )
    {
        $this->assign('modes', OW::getEventManager()->call('mailbox.get_active_mode_list'));
    }

    public function getConversationList( $foo, $params )
    {
        $resultList = $userList = $userIdList = array();
        $from = empty($params['offset']) ? 0 : (int)$params['offset'];
        $list = OW::getEventManager()->call('mailbox.get_chat_user_list', array(
            'userId' => OW::getUser()->getId(),
            'from' => $from,
            'count' => self::CONVERSATION_LIMIT
        ));

        foreach ( $list as $conversation )
        {
            $userIdList[] = $conversation['opponentId'];
        }

        foreach (BOL_UserService::getInstance()->findUserListByIdList($userIdList) as $user )
        {
            $userList[$user->id] = $user;
        }

        foreach ( $list as $conversation )
        {
            if ( isset($userList[$conversation['opponentId']]) )
            {
                $resultList[] = $conversation;
            }
        }
        
        $this->assign('result', array(
            'error' => false,
            'winks' => SKANDROID_ABOL_Service::getInstance()->getWinkRequests(),
            'modes' => OW::getEventManager()->call('mailbox.get_active_mode_list'),
            'list' => SKANDROID_ABOL_MailboxService::getInstance()->prepareConversationList($resultList),
            'billingInfo' => $this->service->getBillingInfo(array(
                SKANDROID_ABOL_MailboxService::ACTION_SEND_CHAT_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_READ_CHAT_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_REPLY_CHAT_MESSAGE,

                SKANDROID_ABOL_MailboxService::ACTION_SEND_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_READ_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_REPLY_TO_MESSAGE
            ))
        ));
    }

    public function getConversationMessages( $params )
    {
        if ( empty($params['conversationId']) )
        {
            $this->assign('result', array('list'=>array()));

            return;
        }

        $userId = OW::getUser()->getId();
        $conversationId = $params['conversationId'];

        $result = OW::getEventManager()->call('mailbox.get_messages', array(
            'userId' => $userId,
            'conversationId' => $conversationId
        ));
        $list = $this->service->prepareMessageList($result['list']);

        $idList = array();

        foreach ( $list as $message )
        {
            if ( !$message['isAuthor'] && $message['readMessageAuthorized'] )
            {
                $idList[] = $message['id'];
            }
        }

        if ( !empty($idList) )
        {
            MAILBOX_BOL_ConversationService::getInstance()->markMessageIdListRead($idList);
        }

        $this->assign('result', array(
            'list' => $list,
            'count' => $this->service->countUnreadMessage($conversationId, $userId),
            'billingInfo' => $this->service->getBillingInfo(array(
                SKANDROID_ABOL_MailboxService::ACTION_SEND_CHAT_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_READ_CHAT_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_REPLY_CHAT_MESSAGE,

                SKANDROID_ABOL_MailboxService::ACTION_SEND_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_READ_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_REPLY_TO_MESSAGE
            ))
        ));
    }

    public function getConversationHistory( $params )
    {
        $userId = OW::getUser()->getId();
        $opponentId = $params['opponentId'];
        $beforeMessageId = $params['beforeMessageId'];

        $result = OW::getEventManager()->call('mailbox.get_history', array(
            'userId' => $userId,
            'opponentId' => $opponentId,
            'beforeMessageId' => $beforeMessageId
        ));

        $list = $this->service->prepareMessageList($result['log']);
        $this->assign('result', array(
            'list' => $list
        ));
    }

    public function getUnreadMessageCount( $params )
    {
        if (!OW::getUser()->isAuthenticated())
        {
            $this->assign("count", 0);
            return;
        }

        $userId = OW::getUser()->getId();

        $count = OW::getEventManager()->call("mailbox.get_unread_message_count", array(
            "userId" => $userId
        ));

        $this->assign("count", $count);
    }

    public function sendMessage( $params )
    {
        $userId = OW::getUser()->getId();
        $opponentId = $params['opponentId'];
        $text = nl2br($params['text']);
        $mode = $params['mode'];
        $lastMessageTimestamp = !empty($params['lastMessageTimestamp']) ? $params['lastMessageTimestamp'] : 0;

        try
        {
            $result = OW::getEventManager()->call('mailbox.post_message', array(
                'mode' => $mode,
                'userId' => $userId,
                'opponentId' => $opponentId,
                'text' => $text
            ));

            $conversationItem = null;

            if ( empty($params['conversationId']) )
            {
                $list = OW::getEventManager()->call('mailbox.get_chat_user_list', array(
                    'userId' => $userId,
                    'count' => 10
                ));

                foreach ( $list as $conv )
                {
                    if ( $conv['conversationId'] == $result['message']['convId'] )
                    {
                        $conversationItem = $conv;

                        break;
                    }
                }

                $list = SKANDROID_ABOL_MailboxService::getInstance()->prepareConversationList(array($conversationItem));
                $conversationItem = $list[0];
            }

            $this->assign('result', array(
                'error' => $result['error'],
                'message' => $result['message'],
                'list' => $this->service->getNewMessages($userId, $opponentId, $lastMessageTimestamp),
                'conversation' => $conversationItem,
                'billingInfo' => $this->service->getBillingInfo(array(
                    SKANDROID_ABOL_MailboxService::ACTION_SEND_CHAT_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_READ_CHAT_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_REPLY_CHAT_MESSAGE,

                    SKANDROID_ABOL_MailboxService::ACTION_SEND_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_READ_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_REPLY_TO_MESSAGE
                ))
            ));
        }
        catch ( Exception $e )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => $e->getMessage()
            ));
        }
    }

    public function postReplyMessage( $params )
    {
        if ( empty($params['uid']) || empty($params['opponentId']) || empty($params['conversationId']) || empty($params['text']) )
        {
            throw new ApiResponseErrorException("Illegal arguments");
        }

        $userId = OW::getUser()->getId();
        $opponentId = $params['opponentId'];
        $conversationId = $params['conversationId'];
        $text = nl2br($params['text']);

        try
        {
            $result = OW::getEventManager()->call('mailbox.post_reply_message', array('mode' => 'mail', 'conversationId' => $conversationId, 'userId' => $userId, 'opponentId' => $opponentId, 'text' => $text));

            if ( $result['error'] )
            {
                $this->assign('result', array(
                    'error' => true,
                    'message' => $result['message']
                ));

                return;
            }

            $conversationService = MAILBOX_BOL_ConversationService::getInstance();
            $uid = 'mailbox_conversation_' . OW::getUser()->getId() . '_' . $params['uid'];
            $files = BOL_AttachmentService::getInstance()->getFilesByBundleName('mailbox', $uid);

            if ( !empty($files) )
            {
                $messageDto = $conversationService->getLastMessage($conversationId);
                $conversationService->addMessageAttachments($messageDto->id, $files);
            }
        }
        catch ( Exception $e )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => $e->getMessage()
            ));

            return;
        }

        $message = $conversationService->getMessage($result['message']['id']);
        $message =  $conversationService->getMessageData($message);
        $message = SKANDROID_ABOL_MailboxService::getInstance()->prepareMessageList(array($message));

        $this->assign('result', array(
            'error' => false,
            'message' => $message[0],
            'billingInfo' => $this->service->getBillingInfo(array(
                SKANDROID_ABOL_MailboxService::ACTION_SEND_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_READ_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_REPLY_TO_MESSAGE
            ))
        ));
    }

    public function uploadAttachment( $params )
    {
        if ( empty($_FILES['file']) || empty($params['opponentId']) )
        {
            throw new ApiResponseErrorException("Files were not uploaded");
        }

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $userId = OW::getUser()->getId();
        $opponentId = $params['opponentId'];
        $lastMessageTimestamp = $params['lastMessageTimestamp'];

        $checkResult = $conversationService->checkUser($userId, $opponentId);

        if ( $checkResult['isSuspended'] )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => $checkResult['suspendReasonMessage']
            ));

            return;
        }

        $conversationId = $conversationService->getChatConversationIdWithUserById($userId, $opponentId);

        if ( empty($conversationId) )
        {
            $actionName = 'send_chat_message';
        }
        else
        {
            $firstMessage = $conversationService->getFirstMessage($conversationId);

            if ( empty($firstMessage) )
            {
                $actionName = 'send_chat_message';
            }
            else
            {
                $actionName = 'reply_to_chat_message';
            }
        }

        if ( !OW::getUser()->isAuthorized('mailbox', $actionName) )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $this->assign('result', array(
                    'error' => true,
                    'message' => $status['msg']
                ));
            }
            else
            {
                if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
                {
                    $language = OW::getLanguage();
                    $this->assign('result', array(
                        'error' => true,
                        'message' => $language->text('mailbox', $actionName.'_permission_denied')
                    ));
                }
            }

            return;
        }

        $attachmentService = BOL_AttachmentService::getInstance();
        $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
        $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);
        $conversationId = $conversationService->getChatConversationIdWithUserById($userId, $opponentId);

        if ( empty($conversationId) )
        {
            $conversation = $conversationService->createChatConversation($userId, $opponentId);
            $conversationId = $conversation->getId();
        }
        else
        {
            $conversation = $conversationService->getConversation($conversationId);
        }

        $uid = UTIL_HtmlTag::generateAutoId('mailbox_conversation_' . $conversationId . '_' . $opponentId);

        try
        {
            $dtoArr = $attachmentService->processUploadedFile('mailbox', $_FILES['file'], $uid, $validFileExtensions, $maxUploadSize);
        }
        catch ( Exception $e )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => $e->getMessage()
            ));

            return;
        }

        $files = $attachmentService->getFilesByBundleName('mailbox', $uid);

        if ( !empty($files) )
        {
            try
            {
                $message = $conversationService->createMessage($conversation, $userId, OW::getLanguage()->text('mailbox', 'attachment'));
                $conversationService->addMessageAttachments($message->id, $files);
                BOL_AuthorizationService::getInstance()->trackAction('mailbox', $actionName);
            }
            catch( Exception $e )
            {
                $this->assign('result', array(
                    'error' => true,
                    'message' => $e->getMessage()
                ));

                return;
            }
        }

        $this->assign('result', array(
            'list' => $this->service->getNewMessages($userId, $opponentId, $lastMessageTimestamp),
            'billingInfo' => $this->service->getBillingInfo(array(
                SKANDROID_ABOL_MailboxService::ACTION_SEND_CHAT_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_READ_CHAT_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_REPLY_CHAT_MESSAGE
            ))
        ));
    }

    public function attachAttachment( array $params )
    {
        if ( empty($params['uid']) || empty($_FILES['attach']))
        {
            throw new ApiResponseErrorException("Files were not uploaded");
        }

        $uid = 'mailbox_conversation_' . OW::getUser()->getId() . '_' . $params['uid'];
        $attachmentService = BOL_AttachmentService::getInstance();

        try
        {
            $dtoArr = $attachmentService->processUploadedFile('mailbox', $_FILES['attach'], $uid);
        }
        catch ( Exception $e )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => $e->getMessage()
            ));

            return;
        }

        $this->assign('result', array(
            'error' => false,
            'downloadUrl' => $dtoArr['url'],
            'size' => $dtoArr['dto']->getSize(),
            'id' => $dtoArr['dto']->getId()
        ));
    }

    public function deleteAttachment( $params )
    {
        if ( empty($params['id']) )
        {
            return;
        }

        BOL_AttachmentService::getInstance()->deleteAttachment(null, $params['id']);
    }

    public function findUser($params)
    {
        if ( empty($params['term']) )
        {
            $this->assign('result', array(
                'error' => false,
                'list' => array()
            ));

            return;
        }

        $result = OW::getEventManager()->call('mailbox.find_user', $params);
        $list = array();
        $defaultAvatar = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();

        foreach ( $result as $data )
        {
            $_data = $data['data'];
            $list[] = array(
                'id' => $_data['opponentId'],
                'avatarUrl' => strcasecmp($_data['avatarUrl'], $defaultAvatar) === 0 ? null : $_data['avatarUrl'],
                'displayName' => $_data['displayName']
            );
        }
        $this->assign('result', array(
            'error' => false,
            'list' => $list
        ));
    }

    public function createConversation( $params )
    {
        if ( empty($params['uid']) || empty($params['opponentId']) || empty($params['subject']) || empty($params['text']) )
        {
            throw new ApiResponseErrorException("Illegal arguments");
        }

        $userId = OW::getUser()->getId();
        $params['userId'] = $userId;

        try
        {
            $params['text'] = nl2br($params['text']);
            $conversation = OW::getEventManager()->call('mailbox.create_conversation', $params);
            BOL_AuthorizationService::getInstance()->trackAction('mailbox', 'send_message');
        } catch ( Exception $e )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => $e->getMessage()
            ));

            return;
        }

        if ( !empty($conversation) )
        {
            $conversationService = MAILBOX_BOL_ConversationService::getInstance();
            $messageDto = $conversationService->getLastMessage($conversation->id);
            $uid = 'mailbox_conversation_' . OW::getUser()->getId() . '_' . $params['uid'];
            $files = BOL_AttachmentService::getInstance()->getFilesByBundleName('mailbox', $uid);

            if ( !empty($files) )
            {
                $conversationService->addMessageAttachments($messageDto->id, $files);
            }

            $list = OW::getEventManager()->call('mailbox.get_chat_user_list', array(
                'userId' => $userId,
                'count' => 10
            ));


            foreach ( $list as $conv )
            {
                if ( $conv['conversationId'] == $conversation->id )
                {
                    $conversationItem = $conv;

                    break;
                }
            }

            $list = SKANDROID_ABOL_MailboxService::getInstance()->prepareConversationList(array($conversationItem));

            $this->assign('result', array(
                'error' => false,
                'conversation' => $list[0],
                'billingInfo' => $this->service->getBillingInfo(array(
                    SKANDROID_ABOL_MailboxService::ACTION_SEND_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_READ_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_REPLY_TO_MESSAGE
                ))
            ));
        }
    }

    public function markAsRead( $params )
    {
        $userId = OW::getUser()->getId();
        $conversationList = json_decode($params['conversationId'], true);

        if ( !is_array($conversationList) )
        {
            $conversationList = array($conversationList);
        }

        $result = SKANDROID_ABOL_MailboxService::getInstance()->markConversationAsRead($userId, $conversationList);

        $this->assign('result', $result);
    }

    public function markUnRead( $params )
    {
        $userId = OW::getUser()->getId();
        $conversationList = json_decode($params['conversationId'], true);

        $result = OW::getEventManager()->call('mailbox.mark_unread', array(
            'userId' => $userId,
            'conversationId' => $conversationList
        ));

        $this->assign('result', $result);
    }

    public function deleteConversation( $params )
    {
        $userId = OW::getUser()->getId();
        $conversationList = json_decode($params['conversationId'], true);

        $result = OW::getEventManager()->call('mailbox.delete_conversation', array(
            'userId' => $userId,
            'conversationId' => $conversationList
        ));

        $this->assign('result', $result);
    }

    public function getRecipientInfo( $params )
    {
        if ( empty($params['recipientId']) )
        {
            throw new ApiResponseErrorException('RecipientId required');
        }

        $recipientId = $params['recipientId'];
        $displayName = BOL_UserService::getInstance()->getDisplayName($recipientId);
        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($recipientId);

        $this->assign('result', array(
            'error' => false,
            'info' => array(
                'opponentId' => $recipientId,
                'displayName' => $displayName,
                'avatarUrl' => $avatarUrl
            ),
            'billingInfo' => $this->service->getBillingInfo(array(
                SKANDROID_ABOL_MailboxService::ACTION_SEND_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_READ_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_REPLY_TO_MESSAGE
            ))
        ));
    }

    public function getChatRecipientInfo( $params )
    {
        if ( empty($params['recipientId']) )
        {
            throw new ApiResponseErrorException('RecipientId required');
        }

        $userId = OW::getUser()->getId();
        $recipientId = $params['recipientId'];
        $conversationId = OW::getEventManager()->call('mailbox.get_conversation_id', array(
            'userId' => $userId,
            'opponentId' => $recipientId
        ));

        $list = array();

        if ( !empty($conversationId) )
        {
            $this->getConversationMessages(array('conversationId' => $conversationId));
            $assignVars = $this->assignedVars;
            $list = $assignVars['result']['list'];

            foreach ( $assignVars as $key => $val )
            {
                $this->clearAssign($key);
            }
        }

        $displayName = BOL_UserService::getInstance()->getDisplayName($recipientId);
        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($recipientId);

        $info = array(
            'opponentId' => $recipientId,
            'displayName' => $displayName,
            'avatarUrl' => $avatarUrl
        );

        $this->assign('result', array(
            'error' => false,
            'list' => $list,
            'info' => $info,
            'billingInfo' => $this->service->getBillingInfo(array(
                SKANDROID_ABOL_MailboxService::ACTION_SEND_CHAT_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_READ_CHAT_MESSAGE,
                SKANDROID_ABOL_MailboxService::ACTION_REPLY_CHAT_MESSAGE
            ))
        ));
    }

    public function getAuthorizeInfo( $params )
    {
        if ( empty($params['actionNames']) )
        {
            throw new ApiResponseErrorException("Action names required");
        }

        $actionNames = json_decode($params['actionNames'], true);
        $this->assign('billingInfo', $this->service->getBillingInfo($actionNames));

    }

    public function authorize( $params )
    {
        if ( empty($params['messageId']) )
        {
            throw new ApiResponseErrorException("messageId required");
        }

        $result = MAILBOX_BOL_AjaxService::getInstance()->authorizeActionForApi(array('actionParams' => 'foo_'.$params['messageId']));

        if ( isset($result['error']) )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => strip_tags($result['error'])
            ));
        }
        else
        {
            if ( $result['readMessageAuthorized'] )
            {
                MAILBOX_BOL_ConversationService::getInstance()->markMessageIdListRead(array($result['id']));
            }

            $result = $this->service->prepareMessageList(array($result));
            $this->assign('result', array(
                'error' => false,
                'message' => $result[0],
                'count' => $this->service->countUnreadMessage($result[0]['convId'], OW::getUser()->getId()),
                'billingInfo' => $this->service->getBillingInfo(array(
                    SKANDROID_ABOL_MailboxService::ACTION_READ_CHAT_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_REPLY_CHAT_MESSAGE,

                    SKANDROID_ABOL_MailboxService::ACTION_READ_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_REPLY_TO_MESSAGE
                ))
            ));
        }
    }

    public function winkBack( $params )
    {
        if ( empty($params['messageId']) )
        {
            throw new ApiResponseErrorException('Message id required');
        }

        $message = MAILBOX_BOL_ConversationService::getInstance()->getMessage($params['messageId']);

        try
        {
            $winkResult = OW::getEventManager()->call('winks.winkBack', array(
                'userId' => $message->senderId,
                'partnerId' => $message->recipientId,
                'messageId' => $message->id,
                'sendNotification' => true
            ));

            if ( !empty($winkResult['result']) )
            {
                SKANDROID_ABOL_MailboxService::getInstance()->sendWinkNotification($winkResult['partnerId'], $winkResult['userId']);
            }
        }
        catch( Exception $e )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => $e->getMessage()
            ));

            return;
        }

        if ( isset($winkResult['result']) && $winkResult['result'] === false )
        {
            $this->assign('result', array(
                'error' => true,
                'message' => strip_tags($winkResult['msg'])
            ));
        }
        else
        {
            $message = MAILBOX_BOL_ConversationService::getInstance()->getMessageDataForApi($message);
            $message = $this->service->prepareMessageList(array($message));

            $this->assign('result', array(
                'error' => false,
                'message' => $message[0],
                'count' => $this->service->countUnreadMessage($message[0]['convId'], OW::getUser()->getId()),
                'billingInfo' => $this->service->getBillingInfo(array(
                    SKANDROID_ABOL_MailboxService::ACTION_READ_CHAT_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_REPLY_CHAT_MESSAGE,

                    SKANDROID_ABOL_MailboxService::ACTION_READ_MESSAGE,
                    SKANDROID_ABOL_MailboxService::ACTION_REPLY_CHAT_MESSAGE
                ))
            ));
        }
    }
}
