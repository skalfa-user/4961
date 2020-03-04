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
class SKADATEIOS_ACTRL_Guests extends OW_ApiActionController
{
    public function getList( $params )
    {
        $data = OW::getEventManager()->call("guests.get_guests_list", array(
            "userId" => OW::getUser()->getId()
        ));
        
        if ( empty($data) )
        {
            $this->assign("list", array());
            
            return;
        }
        

        $idList = array();
        $viewedMap = array();
        $timeMap = array();
        foreach ( $data as $item )
        {
            $idList[] = $item["userId"];
            $viewedMap[$item["userId"]] = $item["viewed"];
            $timeMap[$item["userId"]] = UTIL_DateTime::formatDate($item["timeStamp"]);
        }
        
        OW::getEventManager()->call("guests.mark_guests_viewed", array(
            "userId" => OW::getUser()->getId(),
            "guestIds" => $idList
        ));
        
        $bookmarkList = OW::getEventManager()->call("bookmarks.get_mark_list", array(
            "userId" => OW::getUser()->getId(),
            "idList" => $idList
        ));
        
        $bookmarkList = empty($bookmarkList) ? array() : $bookmarkList;
        
        $avatarList = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList, true, false);
        $onlineMap = BOL_UserService::getInstance()->findOnlineStatusForUserList($idList);
        
        foreach ( $avatarList as $userId => $user )
        {
            $color = array('r' => '100', 'g' => '100', 'b' => '100');
            if ( !empty($user['labelColor']) )
            {
                $_color = explode(', ', trim($user['labelColor'], 'rgba()'));
                $color = array('r' => $_color[0], 'g' => $_color[1], 'b' => $_color[2]);
            }
            
            $list[] = array(
                "userId" => $userId,
                "displayName" => $user["title"],
                "avatarUrl" => $user["src"],
                "label" => $user["label"],
                "labelColor" => $color,
                "viewed" => $viewedMap[$userId],
                "online" => $onlineMap[$userId],
                "bookmarked" => !empty($bookmarkList[$userId]),
                "time" => $timeMap[$userId]
            );
        }
        
        $this->assign("list", $list);
    }
}