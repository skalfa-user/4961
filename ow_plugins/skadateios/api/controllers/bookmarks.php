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
class SKADATEIOS_ACTRL_Bookmarks extends OW_ApiActionController
{
    public function markUser( $params )
    {
        $userId = $params["userId"];
        
        if ( $params["mark"] )
        {
            OW::getEventManager()->call("bookmarks.mark", array(
                "userId" => OW::getUser()->getId(),
                "markUserId" => $userId
            ));
        }
        else 
        {
            OW::getEventManager()->call("bookmarks.unmark", array(
                "userId" => OW::getUser()->getId(),
                "markUserId" => $userId
            ));
        }
    }
    
    public function getList( $params )
    {
        $idList = OW::getEventManager()->call("bookmarks.get_user_list", array(
            "userId" => OW::getUser()->getId()
        ));
        
        if ( empty($idList) )
        {
            $this->assign("list", array());
            
            return;
        }
        
        $avatarList = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList, true, false);
        $onlineMap = BOL_UserService::getInstance()->findOnlineStatusForUserList($idList);
        
        $bookmarkList = OW::getEventManager()->call("bookmarks.get_mark_list", array(
            "userId" => OW::getUser()->getId(),
            "idList" => $idList
        ));
        
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
                "displayName" => empty($user["title"]) ? "" : $user["title"],
                "avatarUrl" => $user["src"],
                "label" => $user["label"],
                "labelColor" => $color,
                "online" => $onlineMap[$userId],
                "bookmarked" => $bookmarkList[$userId]
            );
        }
        
        $this->assign("list", $list);
    }
}