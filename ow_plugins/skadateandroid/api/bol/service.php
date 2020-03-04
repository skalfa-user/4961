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
 * iOS API Service.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.skadateios.bol
 * @since 1.0
 */
class SKANDROID_ABOL_Service
{
    const MENU_TYPE_AVATAR = 1;
    const MENU_TYPE_MAIN = 2;
    const MENU_TYPE_BOTTOM = 3;

    /**
     * Class instance
     *
     * @var SKANDROID_ABOL_Service
     */
    private static $classInstance;

    /**
     * @var BOL_UserService
     */
    private $userService;

    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->userService = BOL_UserService::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return SKANDROID_ABOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function addRoute( $name, $path, $ctrl, $action )
    {
        OW::getRouter()->addRoute(new OW_Route($name, "android/" . $path, $ctrl, $action));
    }

    public function getMenu( $userId )
    {
        $items = array();
        $pluginManager = OW::getPluginManager();

        $items[] = array("type" => self::MENU_TYPE_AVATAR, "key" => "user", "label" => $this->userService->getDisplayName($userId),
            "avatarUrl" => BOL_AvatarService::getInstance()->getAvatarUrl($userId, 2));

        if ( $pluginManager->isPluginActive('usearch') )
        {
            $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "search", "count" => 0);
        }

        if ( $pluginManager->isPluginActive('mailbox') )
        {
            $modes = OW::getEventManager()->call('mailbox.get_active_mode_list');
            $count = MAILBOX_BOL_MessageDao::getInstance()->findUnreadMessages($userId, array(), time(), $modes);
            $count = count($count);
//            $count = OW::getEventManager()->call("mailbox.get_unread_message_count", array(
//                "userId" => $userId
//            ));
//            $count = MAILBOX_BOL_ConversationService::getInstance()->getUnreadMessageCount($userId);

            if ( $pluginManager->isPluginActive('winks') )
            {
                $count += WINKS_BOL_Service::getInstance()->
                    countWinksForUser($userId, array(WINKS_BOL_WinksDao::STATUS_WAIT));
            }

            $items[] = array("type" => self::MENU_TYPE_MAIN, 'key' => 'mailbox', 'label' => $this->text("main_menu_messages"),
                'count' => $count);
        }

        if ( $pluginManager->isPluginActive('matchmaking') )
        {
            $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "matches", "count" => 0);
        }

        $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "speed_match", "count" => 0);

        if ( $pluginManager->isPluginActive('ocsguests') )
        {
            $count = OW::getEventManager()->call("guests.get_new_guests_count", array("userId" => $userId));
            $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "guests", "count" => $count);
        }

        if ( $pluginManager->isPluginActive("bookmarks") )
        {
            $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "bookmarks", "count" => 0);
        }

        if ( $this->isBillingEnabled() )
        {
            if ( $pluginManager->isPluginActive("membership") && $pluginManager->isPluginActive("usercredits") )
            {
                $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "memberships_and_credits", "count" => 0);
            }
            else if ( $pluginManager->isPluginActive("membership") )
            {
                $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "memberships", "count" => 0);
            }
            else if ( $pluginManager->isPluginActive("usercredits") )
            {
                $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "credits", "count" => 0);
            }
        }

        //$items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "subscribe", "count" => 0);
        $items[] = array("type" => self::MENU_TYPE_MAIN, "key" => "about", "count" => 0);
        $items[] = array("type" => self::MENU_TYPE_BOTTOM, 'key' => "terms");
        $items[] = array("type" => self::MENU_TYPE_BOTTOM, 'key' => "logout");

        return $items;
    }

    public function isBillingEnabled()
    {
        $enabled = OW::getConfig()->getValue('skandroid', 'billing_enabled');

        if ( !$enabled )
        {
            return false;
        }

        $pm = OW::getPluginManager();

        if ( $pm->isPluginActive('membership') || $pm->isPluginActive('usercredits') )
        {
            return true;
        }

        return false;
    }

    /**
     * @param string $key
     * @param array $vars
     * @return string
     */
    public function text( $key, $vars = array() )
    {
        return OW::getLanguage()->text("skandroid", $key, $vars);
    }
    /*     * *********************************************************************************************************************** */

    public function getNewItemsCount( $menu = null )
    {
        if ( !$menu )
        {
            $menu = $this->getMenu(OW::getUser()->getId());
        }

        $counter = 0;

        foreach ( $menu as $item )
        {
            if ( !empty($item['counter']) )
            {
                $counter += (int) $item['counter'];
            }
        }

        return $counter;
    }

    public function getCustomPage( $uri )
    {
        $document = BOL_DocumentDao::getInstance()->findStaticDocument($uri);

        if ( $document === null )
        {
            return null;
        }

        return OW::getLanguage()->text('base', "local_page_content_{$document->getKey()}");
    }

    public function getUserCurrentLocation( $userId )
    {
        if ( !$userId )
        {
            return false;
        }

        return SKADATE_BOL_CurrentLocationDao::getInstance()->findByUserId($userId);
    }

    public function setUserCurrentLocation( $userId, $latitude, $longitude )
    {
        if ( !$userId )
        {
            return false;
        }

        $location = $this->getUserCurrentLocation($userId);

        if ( !$location )
        {
            $location = new SKADATE_BOL_CurrentLocation();
            $location->userId = $userId;
        }

        $location->latitude = floatval($latitude);
        $location->longitude = floatval($longitude);
        $location->updateTimestamp = time();

        SKADATE_BOL_CurrentLocationDao::getInstance()->save($location);

        return true;
    }

    public function getAuthorizationActions()
    {
        $event = new BASE_CLASS_EventCollector(SKANDROID_ACLASS_EventHandler::EVENT_COLLECT_AUTHORIZATION_ACTIONS);
        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        $result = array();
        if ( !$data )
        {
            return $result;
        }

        $authService = BOL_AuthorizationService::getInstance();

        $groupList = $authService->getGroupList();
        $actionList = $authService->getActionList();

        foreach ( $data as $value )
        {
            $groupName = key($value);
            $group = $value[$groupName];

            $actions = array();
            foreach ( $group['actions'] as $actionName => $actionLabel )
            {
                $actions[] = array(
                    'name' => $actionName,
                    'id' => $this->getAuthorizationActionId($groupName, $actionName, $groupList, $actionList),
                    'label' => $actionLabel
                );
            }

            $group['name'] = $groupName;
            $group['actions'] = $actions;
            $result[] = $group;
        }

        return $result;
    }

    private function getAuthorizationActionId( $groupName, $actionName, $groupList, $actionList )
    {
        foreach ( $groupList as $group )
        {
            if ( $group->name == $groupName )
            {
                foreach ( $actionList as $action )
                {
                    if ( $action->groupId == $group->id && $action->name == $actionName )
                    {
                        return $action->id;
                    }
                }
                break;
            }
        }

        return null;
    }

    public function getAuthorizationActionStatus( $groupName, $actionName = null, array $extra = null )
    {
        $authService = BOL_AuthorizationService::getInstance();

        $userId = OW::getUser()->isAuthenticated() ? OW::getUser()->getId() : 0;
        $isAuthorized = $authService->isActionAuthorizedBy($groupName, $actionName, $extra);

        if ( $isAuthorized['status'] )
        {
            return array('status' => BOL_AuthorizationService::STATUS_AVAILABLE, 'msg' => null, 'authorizedBy' => $isAuthorized['authorizedBy']);
        }

        $lang = OW::getLanguage();

        if ( !$this->isBillingEnabled() )
        {
            return array(
                'status' => BOL_AuthorizationService::STATUS_DISABLED,
                'msg' => $lang->text('base', 'authorization_failed_feedback')
            );
        }

        $error = array(
            'status' => BOL_AuthorizationService::STATUS_DISABLED,
            'msg' => $lang->text('base', 'authorization_failed_feedback')
        );

        // layer check
        $eventParams = array(
            'userId' => $userId,
            'groupName' => $groupName,
            'actionName' => $actionName,
            'extra' => $extra
        );
        $event = new BASE_CLASS_EventCollector('authorization.layer_check_collect_error', $eventParams);
        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        if ( !$data )
        {
            return $error;
        }

        usort($data, array($this, 'sortLayersByPriorityAsc'));

        $links = array();
        foreach ( $data as $option )
        {
            if ( !empty($option['label']) )
            {
                $links[] = strtolower($option['label']);
            }
        }

        if ( count($links) )
        {
            $actionLabel = $this->getAuthorizationActionLabel($groupName, $actionName);

            $error = array(
                'status' => BOL_AuthorizationService::STATUS_PROMOTED,
                'msg' => $lang->text(
                    'base', 'authorization_action_promotion',
                    array('alternatives' => implode(' ' . $lang->text('base', 'or') . ' ', $links), 'action' => strtolower($actionLabel))
                )
            );
        }

        return $error;
    }

    public function getAndroidAvailablePluginList()
    {
        return array('photo', 'mailbox', 'usearch', 'hotlist', 'bookmarks', 'ocsguests', 'membership', 'usercredits', 'googlelocation',
            'base', 'skadate');
    }

    public function sortLayersByPriorityAsc( $el1, $el2 )
    {
        if ( $el1['priority'] === $el2['priority'] )
        {
            return 0;
        }

        return $el1['priority'] > $el2['priority'] ? 1 : -1;
    }

    public function getAuthorizationActionLabel( $searchGroupName, $searchActionName )
    {
        $event = new BASE_CLASS_EventCollector(SKANDROID_ACLASS_EventHandler::EVENT_COLLECT_AUTHORIZATION_ACTIONS);
        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        if ( !$data )
        {
            return '';
        }

        foreach ( $data as $value )
        {
            $groupName = key($value);
            $group = $value[$groupName];
            if ( $groupName != $searchGroupName )
            {
                continue;
            }

            foreach ( $group['actions'] as $actionName => $actionLabel )
            {
                if ( $actionName == $searchActionName )
                {
                    return $actionLabel;
                }
            }
        }

        return 'do this action';
    }

    public function findProductByItunesProductId( $productId )
    {
        $entityKey = strtolower(substr($productId, 0, strrpos($productId, '_')));
        $entityId = (int) substr($productId, strrpos($productId, '_') + 1);

        if ( !strlen($entityKey) || !$productId )
        {
            return null;
        }

        $pm = OW::getPluginManager();
        $return = array();

        switch ( $entityKey )
        {
            case 'membership_plan':
                if ( !$pm->isPluginActive('membership') )
                {
                    return null;
                }

                $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();

                $plan = $membershipService->findPlanById($entityId);
                if ( !$plan )
                {
                    return null;
                }

                $type = $membershipService->findTypeById($plan->typeId);
                $return['pluginKey'] = 'membership';
                $return['entityDescription'] = $membershipService->getFormattedPlan($plan->price, $plan->period,
                    $plan->recurring, $plan->periodUnits);
                $return['membershipTitle'] = $membershipService->getMembershipTitle($type->roleId);
                $return['price'] = floatval($plan->price);
                $return['period'] = $plan->period;
                $return['recurring'] = $plan->recurring;
                $return['periodUnits'] = $plan->periodUnits;

                break;

            case 'user_credits_pack':
                if ( !$pm->isPluginActive('usercredits') )
                {
                    return null;
                }

                $creditsService = USERCREDITS_BOL_CreditsService::getInstance();

                $pack = $creditsService->findPackById($entityId);
                if ( !$pack )
                {
                    return null;
                }

                $return['pluginKey'] = 'usercredits';
                $return['entityDescription'] = $creditsService->getPackTitle($pack->price, $pack->credits);
                $return['price'] = floatval($pack->price);
                $return['period'] = 30;
                $return['recurring'] = 0;

                break;
        }

        $return['entityKey'] = $entityKey;
        $return['entityId'] = $entityId;

        return $return;
    }

    /*
     * Prepare question options for sign up
     *
     * return array
     */

    public function formatOptionsForQuestion( $name, $allOptions )
    {
        $options = array();
        $questionService = BOL_QuestionService::getInstance();

        if ( !empty($allOptions[$name]) )
        {
            $optionList = array();
            foreach ( $allOptions[$name]['values'] as $option )
            {
                $optionList[] = array(
                    'label' => $questionService->getQuestionValueLang($option->questionName, $option->value),
                    'value' => $option->value
                );
            }

            $allOptions[$name]['values'] = $optionList;
            $options = $allOptions[$name];
        }

        return $options;
    }

    public function getWinkRequests( $offset = 0, $limit = 10 )
    {
        $winks = array();

        if ( OW::getPluginManager()->isPluginActive('winks') )
        {
            $winks = WINKS_BOL_Service::getInstance()->
                findWinkListByStatus(OW::getUser()->getId(), $offset, $limit, WINKS_BOL_WinksDao::STATUS_WAIT);

            $resultWinks = array();

            foreach ( $winks as $item )
            {
                $resultWinks[] = array(
                    "date" => $item["date"],
                    "userId" => 0,
                    "mode" => 1,
                    "conversationRead" => (bool)$item["viewed"],
                    "avatarLabel" => "",
                    "displayName" => $item["displayName"],
                    "dateLabel" => $item["date"],
                    "reply" => true,
                    "newMessageCount" => 0,
                    "hasAttachment" => false,
                    "winkReceived" => 0,
                    "shortUserData" => "",
                    "avatarUrl" => $item["avatar"],
                    "opponentId" => $item["userId"],
                    "subject" => "",
                    "previewText" => $item["text"],
                    "onlineStatus" => $item["userOnline"],
                    "timeLabel" => $item["date"],
                    "conversationId" => -$item["id"],
                    "lastMessageTimestamp" => $item["timestamp"]+3600*240,
                    "conversationViewed" => (bool)$item["viewed"],
                    "url" => "",
                    "isOnline" => false,
                    "isDeleted" => false
                );
            }
            
            return $resultWinks;
        }
    }
}
