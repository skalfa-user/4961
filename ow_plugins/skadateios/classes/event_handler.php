<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
class SKADATEIOS_CLASS_EventHandler
{
    private static $instance;

    public static function getInstance()
    {
        if ( self::$instance === null )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {

    }

    public function onAfterUserUnregister( OW_Event $event )
    {
        $params = $event->getParams();

        SKADATEIOS_BOL_PushService::getInstance()->unregisteUserDevices($params["userId"]);
    }

    public function getPromoInfo( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('skadateios' => array(
            'app_url' => OW::getConfig()->getValue('skadateios', 'app_url'),
            'smart_banner_enable' => (bool) OW::getConfig()->getValue('skadateios', 'smart_banner')
        )));
    }

    public function afterMailboxMessageSent( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !empty($params["isSystem"]) )
        {
            return;
        }

        /* @var $message MAILBOX_BOL_Message */
        $message = $event->getData();

        $userId = $params["recipientId"];
        $senderId = $params["senderId"];
        $conversationId = $params["conversationId"];
        $text = $params["message"];

        $senderName = BOL_UserService::getInstance()->getDisplayName($senderId);

        SKADATEIOS_BOL_PushService::getInstance()->sendToUserDevices($userId, array(
            "key" => "skadateios+push_new_message",
            "vars" => array(
                "sender" => $senderName,
                "message" => $text
            )
        ), array(
            "type" => SKADATEIOS_BOL_PushService::TYPE_MESSAGE,
            "conversationId" => (int) $conversationId,
            "senderId" => (int) $senderId
        ));
    }

    public function afterGuestVisit( OW_Event $event )
    {
        $params = $event->getParams();
        $userId = $params["userId"];
        $guestId = $params["guestId"];

        if ( !$params["new"] )
        {
            return;
        }

        $guestName = BOL_UserService::getInstance()->getDisplayName($guestId);

        SKADATEIOS_BOL_PushService::getInstance()->sendToUserDevices($userId, array(
            "key" => "skadateios+push_new_profile_view",
            "vars" => array(
                "guest" => $guestName
            )
        ), array(
            "type" => SKADATEIOS_BOL_PushService::TYPE_GUEST,
            "guestId" => (int) $guestId
        ));
    }

    public function afterWinkSent( OW_Event $event )
    {
        $params = $event->getParams();

        $senderId = $params["userId"];
        $userId = $params["partnerId"];

        $senderName = BOL_UserService::getInstance()->getDisplayName($senderId);

        SKADATEIOS_BOL_PushService::getInstance()->sendToUserDevices($userId, array(
            "key" => "skadateios+push_new_wink",
            "vars" => array(
                "sender" => $senderName
            )
        ), array(
            "type" => SKADATEIOS_BOL_PushService::TYPE_WINK,
            "senderId" => (int) $senderId
        ));
    }

    public function afterSpeedMatch( OW_Event $event )
    {
        $params = $event->getParams();

        $opponentId = $params["userId"];
        $userId = $params["opponentId"];
        $conversationId = $params["conversationId"];

        $opponentName = BOL_UserService::getInstance()->getDisplayName($opponentId);

        SKADATEIOS_BOL_PushService::getInstance()->sendToUserDevices($userId, array(
            "key" => "skadateios+push_speed_match",
            "vars" => array(
                "opponent" => $opponentName
            )
        ), array(
            "type" => SKADATEIOS_BOL_PushService::TYPE_SPEEDMATCH,
            "opponentId" => (int) $opponentId,
            "conversationId" => (int) $conversationId
        ));
    }

    public function genericInit()
    {
        OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, array($this, "onAfterUserUnregister"));

        // Push Notifications

        if ( SKADATEIOS_BOL_PushService::getInstance()->isPushEnabled() )
        {
            OW::getEventManager()->bind('mailbox.send_message', array($this, 'afterMailboxMessageSent'));
            OW::getEventManager()->bind('guests.after_visit', array($this, 'afterGuestVisit'));
            OW::getEventManager()->bind('winks.send_wink', array($this, 'afterWinkSent'));
            OW::getEventManager()->bind('speedmatch.after_match', array($this, 'afterSpeedMatch'));
        }
    }

    public function init()
    {
        OW::getEventManager()->bind('app.promo_info', array($this, 'getPromoInfo'));

        $this->genericInit();
    }
}
