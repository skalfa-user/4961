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
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_system_plugins.skadateios.api.controllers
 * @since 1.0
 */
class SKADATEIOS_ACTRL_Matches extends OW_ApiActionController
{
    public function getList( $params )
    {
        $sort = empty($params["sort"]) ? "newest" : $params["sort"];

        $matchList = OW::getEventManager()->call("matchmaking.get_list", array(
            "userId" => OW::getUser()->getId(),
            "sort" => $sort, // newest or compatible
            "first" => empty($params["first"]) ? 0 : $params["first"],
            "count" => empty($params["count"]) ? 0 : $params["count"]
        ));

        $idList = array();
        $compatibilityList = array();
        
        foreach ( $matchList as $item )
        {
            $idList[] = $item["id"];
            $compatibilityList[$item["id"]] = $item["compatibility"];
        }
        
        $userData = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList, false, false, true, true);
        $questionsData = BOL_QuestionService::getInstance()->getQuestionData($idList, array("googlemap_location", "birthdate"));
        
        foreach ( $questionsData as $userId => $data )
        {
            $date = UTIL_DateTime::parseDate($data['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
            $userData[$userId]["ages"] = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            
            $userData[$userId]["location"] = empty($data["googlemap_location"]["address"]) ? null : $data["googlemap_location"]["address"];
        }
        
        $photoList = array();
        $avatarList = array();
        
        foreach ( $idList as $userId )
        {
            $bigAvatar = SKADATE_BOL_Service::getInstance()->findAvatarByUserId($userId);
            $avatarList[$userId] = $bigAvatar 
                    ? SKADATE_BOL_Service::getInstance()->getAvatarUrl($userId, $bigAvatar->hash) 
                    : BOL_AvatarService::getInstance()->getAvatarUrl($userId, 2);
            
            $event = new OW_Event('photo.getMainAlbum', array('userId' => $userId));
            OW::getEventManager()->trigger($event);
            $album = $event->getData();
            
            $photos = !empty($album['photoList']) ? $album['photoList'] : array();
            
            $photoList[$userId] = array();
            
            foreach ( $photos as $photo )
            {
                $photoList[$userId][] = array(
                    "src" => $photo["url"]["main"]
                );
            }
        }
        
        $bookmarksList = OW::getEventManager()->call("bookmarks.get_mark_list", array(
            "userId" => OW::getUser()->getId(),
            "idList" => $idList
        ));
        
        $bookmarksList = empty($bookmarksList) ? array() : $bookmarksList;
        
        $list = array();
        foreach ( $idList as $userId )
        {
            $list[] = array(
                "userId" => $userId,
                "photos" => $photoList[$userId],
                "avatar" => $avatarList[$userId],
                "name" => empty($userData[$userId]["title"]) ? "" : $userData[$userId]["title"],
                "label" => $userData[$userId]["label"],
                "labelColor" => $userData[$userId]["labelColor"],
                "compatibility" => $compatibilityList[$userId],
                "location" => empty($userData[$userId]["location"]) ? "" : $userData[$userId]["location"],
                "ages" => $userData[$userId]["ages"],
                "bookmarked" => !empty($bookmarksList[$userId])
            );
        }

        $event = new OW_Event(SKADATEIOS_ACLASS_EventHandler::USER_LIST_PREPARE_USER_DATA, array('listName' => 'match_list'), $list);
        OW_EventManager::getInstance()->trigger($event);

        $this->assign("list", $event->getData());
        
        
        $total = OW::getEventManager()->call("matchmaking.get_list_count", array(
            "userId" => OW::getUser()->getId()
        ));
        
        $this->assign("total", $total);
        
        $service = SKADATEIOS_ABOL_Service::getInstance();
        $auth = array(
            'photo.view' => $service->getAuthorizationActionStatus('photo', 'view')
        );

        $this->assign('auth', $auth);
        
        $allowSendMessage = OW::getPluginManager()->isPluginActive('mailbox');
        
        $this->assign("actions", array(
            "bookmark" => OW::getPluginManager()->isPluginActive('bookmarks'),
            "message" => $allowSendMessage,
            "wink" => OW::getPluginManager()->isPluginActive('winks'),
        ));
        
        $mailboxModes = OW::getEventManager()->call('mailbox.get_active_mode_list');
        $this->assign("mailboxModes", empty($mailboxModes) ? array() : $mailboxModes);
    }
}