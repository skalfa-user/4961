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
 * iOS API Service.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.skadateios.bol
 * @since 1.0
 */
class SKADATEIOS_ABOL_Service
{
    /**
     * Class instance
     *
     * @var SKADATEIOS_ABOL_Service
     */
    private static $classInstance;

    /**
     * Class constructor
     */
    private function __construct()
    {

    }

    /**
     * Returns class instance
     *
     * @return SKADATEIOS_ABOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getMenu( $userId, $type = 'main' )
    {
        $items = array();
        $pm = OW::getPluginManager();

        switch ( $type )
        {
            case 'main':
                if ( $pm->isPluginActive("usearch") )
                {
                    $items[] = array('key' => 'Search', 'label' => 'Search', 'counter' => 0);
                }
                
                if ( $pm->isPluginActive('mailbox') )
                {
                    $activeModes = MAILBOX_BOL_ConversationService::getInstance()->getActiveModeList();
                    $messageList = MAILBOX_BOL_MessageDao::getInstance()->findUnreadMessages($userId, array(), time(), $activeModes);
                    $count = count($messageList); // Hot fix. TODO refactor
                    //$count = MAILBOX_BOL_ConversationService::getInstance()->getUnreadMessageCount($userId);


                    if ( $pm->isPluginActive('winks') )
                    {
                        $count += WINKS_BOL_Service::getInstance()->
                                countWinksForUser($userId, array(WINKS_BOL_WinksDao::STATUS_WAIT));
                    }

                    $items[] = array('key' => 'Mailbox', 'label' => 'Messages', 'counter' => $count);
                }

                if ( $pm->isPluginActive('matchmaking') )
                {
                    $items[] = array('key' => 'Matches', 'label' => 'My Matches', 'counter' => 0);
                    
                    if ( $pm->isPluginActive("googlelocation") )
                    {
                        $items[] = array('key' => 'SpeedMatch', 'label' => 'SpeedMatch', 'counter' => 0);
                    }
                }

                if ( $pm->isPluginActive('ocsguests') )
                {
                    $count = OW::getEventManager()->call('guests.get_new_guests_count', array('userId' => $userId));
                    $items[] = array('key' => 'Guests', 'label' => 'Guests', 'counter' => $count);
                }

                if ( $pm->isPluginActive('bookmarks') )
                {
                    $items[] = array('key' => 'Bookmarks', 'label' => 'Bookmarks', 'counter' => 0);
                }

                if ( SKADATEIOS_ABOL_Service::getInstance()->isBillingEnabled() )
                {
                    $label = 'Membership & Credits';
                    if ( !$pm->isPluginActive("usercredits") )
                    {
                        $label = "membership";
                    }
                    
                    if ( !$pm->isPluginActive("membership") )
                    {
                        $label = "credits";
                    }
                    
                    $items[] = array('key' => 'Billing', 'label' => $label, 'counter' => 0);
                }

                $items[] = array('key' => 'About', 'label' => 'About', 'counter' => 0);

                break;

            case 'bottom':
                //$items[] = array('key' => 'Desktop', 'label' => 'Desktop Version', 'url' => OW_URL_HOME . 'desktop-version');
                $items[] = array('key' => 'Terms', 'label' => 'Terms & Policies', 'custom' => true);

                break;
        }

        return $items;
    }

    public function getWinkRequests($offset = 0, $limit = 10)
    {
        $winks = array();

        if ( OW::getPluginManager()->isPluginActive('winks') )
        {
            $winks = WINKS_BOL_Service::getInstance()->
                    findWinkListByStatus(OW::getUser()->getId(), $offset, $limit, WINKS_BOL_WinksDao::STATUS_WAIT);
        }

        return array(
            'list' => $winks
        );
    }

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

    public function getCustomPage( $key )
    {
        if ( $key == 'Terms' )
        {
            $document = BOL_DocumentDao::getInstance()->findStaticDocument('terms-of-use');
            $content = OW::getLanguage()->text('base', "local_page_content_{$document->getKey()}");

            return $content;
        }

        return 'No content yet';
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
        $event = new BASE_CLASS_EventCollector(SKADATEIOS_ACLASS_EventHandler::EVENT_COLLECT_AUTHORIZATION_ACTIONS);
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

    public function isBillingEnabled()
    {
        $enabled = OW::getConfig()->getValue('skadateios', 'billing_enabled');

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
                'msg' => $lang->text('skadateios', 'action_not_available')
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
                    'base',
                    'authorization_action_promotion',
                    array('alternatives' => implode(' '.$lang->text('base', 'or').' ', $links), 'action' => strtolower($actionLabel))
                )
            );
        }

        return $error;
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
        $event = new BASE_CLASS_EventCollector(SKADATEIOS_ACLASS_EventHandler::EVENT_COLLECT_AUTHORIZATION_ACTIONS);
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
                $return['entityDescription'] = $membershipService->getFormattedPlan($plan->price, $plan->period, $plan->recurring, $plan->periodUnits);
                $return['membershipTitle'] = $membershipService->getMembershipTitle($type->roleId);
                
                $return['price'] = floatval($plan->price);
                $return['period'] = $plan->period;
                $return['recurring'] = $plan->recurring;

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

    public static function dataForUserAvatar( $userId )
    {
        $avatar = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId), false, false);

        $result = $avatar[$userId];
        if ( !empty($result['labelColor']) )
        {
            $color = explode(', ', trim($result['labelColor'], 'rgba()'));
            $result['labelColor'] = array('r' => $color[0], 'g' => $color[1], 'b' => $color[2]);
        }
        else
        {
            $result['labelColor'] = array('r' => '100', 'g' => '100', 'b' => '100');
        }

        $avatarDao = BOL_AvatarService::getInstance()->findByUserId($userId);

        $result["active"] = true;
        if ( $avatarDao !== null )
        {
            $result["src"] = BOL_AvatarService::getInstance()->getAvatarUrl($userId, 2, null, true, false);
            $result["active"] = $avatarDao->status == "active";
        }

        $result["src"] = empty($result["src"]) ? BOL_AvatarService::getInstance()->getDefaultAvatarUrl(2) : $result["src"];
        $result["url"] = $result["src"];

        return $result;
    }
}
