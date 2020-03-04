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
 * @author Zarif Safiullin <zaph.work@gmail.com>
 * @package ow_system_plugins.skadateios.api.controllers
 * @since 1.6.0
 */
class SKADATEIOS_ACTRL_Mailbox extends OW_ApiActionController
{
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

    public function getList( $params )
    {
        $activeModes = OW::getEventManager()->call('mailbox.get_active_mode_list');
        $this->assign('mailModeEnabled', in_array('mail', $activeModes) ? true : false);

        if (!OW::getUser()->isAuthenticated())
        {
            $this->assign("list", array());
            return;
        }

        $from = 0;
        $count = 10;

        if (isset($params['from']))
        {
            $from = (int)$params['from'];
        }

        if (isset($params['count']))
        {
            $count = (int)$params['count'];
        }

        $list = OW::getEventManager()->call("mailbox.get_chat_user_list", array(
            "userId" => OW::getUser()->getId(),
            "from" => $from,
            "count" => $count
        ));

        foreach ( $list as & $item )
        {
            $previewText = $item["previewText"];
            if ( is_array($previewText) && isset($previewText["text"]) )
            {
                $previewText = $previewText["text"];
            }
            
            $item["previewText"] = strip_tags($previewText);
        }
        
        if ( empty($list) )
        {
            $this->assign("list", array());

            return;
        }

        $this->assign("list", $list);
    }

    public function getMessages( $params )
    {
        if (!OW::getUser()->isAuthenticated())
        {
            $this->assign("result", array('list'=>array()));
            return;
        }

        $userId = OW::getUser()->getId();
        $opponentId = empty($params["userId"]) ? null : $params["userId"];
        $conversationId = empty($params['conversationId']) ? null : $params['conversationId'];

        $result = OW::getEventManager()->call('mailbox.get_messages', array(
            'userId' => $userId,
            'conversationId' => $conversationId,
            "opponentId" => $opponentId
        ));

        foreach ( $result['list'] as & $message )
        {
            if ( !empty($message["text"]) && is_string($message["text"]) )
            {
                $message["text"] = strip_tags($message["text"]);
            }
        }
        
        $this->assign('list', $result['list']);
        $this->assign('length', $result['length']);

        $service = SKADATEIOS_ABOL_Service::getInstance();
        $auth = array(
            'mailbox.read_chat_message' => $service->getAuthorizationActionStatus('mailbox', 'read_chat_message'),
            'mailbox.send_chat_message' => $service->getAuthorizationActionStatus('mailbox', 'send_chat_message'),
            'mailbox.reply_to_chat_message' => $service->getAuthorizationActionStatus('mailbox', 'reply_to_chat_message'),
            'mailbox.read_message' => $service->getAuthorizationActionStatus('mailbox', 'read_message'),
            'mailbox.send_message' => $service->getAuthorizationActionStatus('mailbox', 'send_message'),
            'mailbox.reply_to_message' => $service->getAuthorizationActionStatus('mailbox', 'reply_to_message')
        );
        $this->assign('auth', $auth);
    }

    public function getHistory( $params )
    {
        if (!OW::getUser()->isAuthenticated())
        {
            $this->assign("result", array('list'=>array()));
            return;
        }

        $userId = OW::getUser()->getId();
        $opponentId = $params['userId'];

        $result = OW::getEventManager()->call('mailbox.get_history', array('userId'=>$userId, 'opponentId'=>$opponentId, 'beforeMessageId'=>$params['beforeMessageId']));

        $this->assign('list', $result['log']);
    }

    public function getNewMessages( $params )
    {
        if (!OW::getUser()->isAuthenticated())
        {
            $this->assign("result", array('list'=>array()));
            return;
        }

        $userId = OW::getUser()->getId();
        $opponentId = $params['userId'];
        $lastMessageTimestamp = $params['lastMessageTimestamp'];

        $result = OW::getEventManager()->call('mailbox.get_new_messages', array('userId'=>$userId, 'opponentId'=>$opponentId, 'lastMessageTimestamp'=>$lastMessageTimestamp));

        $this->assign('list', $result);
    }

    public function postMessage( $params )
    {
        if (!OW::getUser()->isAuthenticated())
        {
            $this->assign('result', array('error'=>true, 'message'=>'User is not authenticated'));
            return;
        }

        $userId = OW::getUser()->getId();
        $opponentId = $params['userId'];
        $text = $params['text'];

        $result = OW::getEventManager()->call('mailbox.post_message', array('mode'=>'chat', 'userId'=>$userId, 'opponentId'=>$opponentId, 'text'=>$text));

        $this->assign('result', $result);
    }

    public function postReplyMessage( $params )
    {
        if (!OW::getUser()->isAuthenticated())
        {
            $this->assign('result', array('error'=>true, 'message'=>'User is not authenticated'));
            return;
        }

        $userId = OW::getUser()->getId();
        $opponentId = $params['userId'];
        $conversationId = $params['conversationId'];
        $text = $params['text'];

        $result = OW::getEventManager()->call('mailbox.post_reply_message', array('mode'=>'mail', 'conversationId'=>$conversationId, 'userId'=>$userId, 'opponentId'=>$opponentId, 'text'=>$text));

        $this->assign('result', $result);
    }

    //TODO move this function to event
    public function uploadAttachment( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException("Undefined userId");
        }

        if ( empty($_FILES['images']) )
        {
            throw new ApiResponseErrorException("Files were not uploaded");
        }
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        $checkResult = $conversationService->checkUser($params['userId'], $params['opponentId']);

        if ($checkResult['isSuspended'])
        {
            $this->assign('error', true);
            $this->assign('message', $checkResult['suspendReasonMessage']);
            $this->assign('suspendReason', $checkResult['suspendReason']);
            return;
        }

        $attachmentService = BOL_AttachmentService::getInstance();

        $conversationId = $conversationService->getChatConversationIdWithUserById($userId, $params['opponentId']);

        if (empty($conversationId))
        {
            $actionName = 'send_chat_message';
        }
        else
        {
            $firstMessage = $conversationService->getFirstMessage($conversationId);

            if (empty($firstMessage))
            {
                $actionName = 'send_chat_message';
            }
            else
            {
                $actionName = 'reply_to_chat_message';
            }
        }

        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);
            if ($status['status'] == BOL_AuthorizationService::STATUS_PROMOTED)
            {
                $this->assign('error', true);
                $this->assign('message', $status['msg']);
            }
            else
            {
                if ($status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE)
                {
                    $language = OW::getLanguage();
                    $this->assign('error', true);
                    $this->assign('message', $language->text('mailbox', $actionName.'_permission_denied'));
                }
            }

            return;
        }


        $finalFileArr = array();

        foreach ( $_FILES['images'] as $key => $items )
        {
            foreach ( $items as $index => $item )
            {
                if ( !isset($finalFileArr[$index]) )
                {
                    $finalFileArr[$index] = array();
                }

                $finalFileArr[$index][$key] = $item;
            }
        }

        foreach ( $finalFileArr as $item )
        {
            $opponentId = $params['opponentId'];
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

            $uid = UTIL_HtmlTag::generateAutoId('mailbox_conversation_'.$conversationId.'_'.$opponentId);

            try
            {
                $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
                $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);

                $dtoArr = $attachmentService->processUploadedFile('mailbox', $item, $uid, $validFileExtensions, $maxUploadSize);
            }
            catch ( Exception $e )
            {
                throw new ApiResponseErrorException($e->getMessage());
            }

            $files = $attachmentService->getFilesByBundleName('mailbox', $uid);

            if (!empty($files))
            {
                try
                {
                    $message = $conversationService->createMessage($conversation, $userId, OW::getLanguage()->text('mailbox', 'attachment'));
                    $conversationService->addMessageAttachments($message->id, $files);

                    $this->assign('message', $conversationService->getMessageData($message));
                }
                catch(InvalidArgumentException $e)
                {
                    throw new ApiResponseErrorException($e->getMessage());
                }
            }
        }
    }

    public function authorize( $params )
    {
        $result = OW::getEventManager()->call('mailbox.authorize_action', $params);
        $this->assign('result', $result);
    }

    public function markUnread( $params )
    {
        $userId = OW::getUser()->getId();
        $conversationId = $params['conversationId'];

        $result = OW::getEventManager()->call('mailbox.mark_unread', array('userId'=>$userId, 'conversationId'=>$conversationId));
        $this->assign('result', $result);
    }

    public function deleteConversation( $params )
    {
        $userId = OW::getUser()->getId();
        $conversationId = $params['conversationId'];

        $result = OW::getEventManager()->call('mailbox.delete_conversation', array('userId'=>$userId, 'conversationId'=>$conversationId));
        $this->assign('result', $result);
    }

    public function createConversation( $params )
    {
        $userId = OW::getUser()->getId();
        $params['userId'] = $userId;

        $actionName = 'send_message';
        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        
        $result = array();

        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);
            if ($status['status'] == BOL_AuthorizationService::STATUS_PROMOTED)
            {
                $result = array('error' => true, 'message'=>strip_tags($status['msg']), "promoted" => true);
            }
            else
            {
                if ($status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE)
                {
                    $language = OW::getLanguage();
                    $result = array('error' => true, 'message' => $language->text('mailbox', $actionName.'_permission_denied'), "promoted" => false);
                }
            }
        }
        else
        {
            $result = OW::getEventManager()->call('mailbox.create_conversation', $params);
        }
        
        $this->assign('result', $result);
    }

    public function findUser($params)
    {
        $result = OW::getEventManager()->call('mailbox.find_user', $params);
        $this->assign('result', $result);
    }
}