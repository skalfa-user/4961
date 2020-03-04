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

class SKANDROID_CLASS_EventHandler
{
    private static $instance;

    /**
     * @return SKANDROID_CLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$instance === null )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }
    private $pushService;

    private function __construct()
    {
        $this->pushService = SKANDROID_BOL_PushService::getInstance();
    }

    public function genreicInit()
    {
        if ( $this->pushService->isPushEnabled() )
        {
            OW::getEventManager()->bind('mailbox.send_message', array($this, 'afterMailboxMessageSent'));
            OW::getEventManager()->bind('guests.after_visit', array($this, 'afterGuestVisit'));
            OW::getEventManager()->bind('winks.send_wink', array($this, 'afterWinkSent'));
            OW::getEventManager()->bind('speedmatch.after_match', array($this, 'afterSpeedMatch'));
        }
    }

    public function init()
    {
        $this->genreicInit();

        OW::getEventManager()->bind('app.promo_info', array($this, 'getPromoInfo'));

        OW::getEventManager()->bind('admin.add_admin_notification', array($this, 'addAdminNotification'));

    }

    public function getPromoInfo( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('skandroid' => array(
                'app_url' => OW::getConfig()->getValue('skandroid', 'app_url'),
                'smart_banner_enable' => (bool) OW::getConfig()->getValue('skandroid', 'smart_banner')
        )));
    }

    public function afterMailboxMessageSent( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !empty($params["isSystem"]) )
        {
            return;
        }

        $userId = $params["recipientId"];
        $senderId = $params["senderId"];
        $conversationId = $params["conversationId"];

        $senderName = BOL_UserService::getInstance()->getDisplayName($senderId);
        $text = strip_tags($params["message"]);

        if ( mb_strlen($text) > SKANDROID_BOL_PushService::GMC_MSG_MAX_LENGTH )
        {
            $text = mb_substr($text, 0, SKANDROID_BOL_PushService::GMC_MSG_MAX_LENGTH) . "...";
        }

        $this->pushService->sendNotifiation($userId,
            array(
            "key" => "push_new_message",
            "titleKey" => "push_new_message_title",
            "vars" => array(
                "username" => $senderName,
                "message" => $text
            )
            ),
            array(
            "type" => SKANDROID_BOL_PushService::TYPE_MESSAGE,
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

        $this->pushService->sendNotifiation($userId,
            array(
            "key" => "push_new_profile_view",
            "titleKey" => "push_new_profile_view_title",
            "vars" => array(
                "guest" => $guestName
            )
            ),
            array(
            "type" => SKANDROID_BOL_PushService::TYPE_GUEST,
            "guestId" => (int) $guestId
        ));
    }

    public function afterWinkSent( OW_Event $event )
    {
        $params = $event->getParams();

        $senderId = $params["userId"];
        $userId = $params["partnerId"];

        $senderName = BOL_UserService::getInstance()->getDisplayName($senderId);

        $this->pushService->sendNotifiation($userId,
            array(
            "key" => "push_new_wink",
            "titleKey" => "push_new_wink_title",
            "vars" => array(
                "username" => $senderName
            )
            ),
            array(
            "type" => SKANDROID_BOL_PushService::TYPE_WINK,
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

        $this->pushService->sendNotifiation($userId,
            array(
            "key" => "push_speed_match",
            "titleKey" => "push_speed_match_title",
            "vars" => array(
                "username" => $opponentName
            )
            ),
            array(
            "type" => SKANDROID_BOL_PushService::TYPE_SPEEDMATCH,
            "opponentId" => (int) $opponentId,
            "conversationId" => (int) $conversationId
        ));
    }

    public function addAdminNotification( BASE_CLASS_EventCollector $event )
    {
        $configs = OW::getConfig()->getValues('skandroid');
        
        if ( !empty($configs['billing_enabled'])
            && ( empty($configs['service_account_id'])
                || empty($configs['service_account_private_key']) ) )
        {
            $event->add(OW::getLanguage()->text('skandroid', 'plugin_require_configuration',
                ['link' => OW::getRouter()->urlForRoute('skandroid_admin_settings')]));
        }
    }
}
