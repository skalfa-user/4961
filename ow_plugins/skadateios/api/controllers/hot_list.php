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
class SKADATEIOS_ACTRL_HotList extends OW_ApiActionController
{
    private function commonHandler( $params )
    {
        $this->assign("userAdded", OW::getEventManager()->call("hotlist.is_user_added", array(
            "userId" => OW::getUser()->getId()
        )));
        
        $authorized = OW::getUser()->isAuthorized("hotlist", "add_to_list");
        $promoted = false;
        
        if ( !$authorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus("hotlist", "add_to_list");
            $promoted = $status["status"] == BOL_AuthorizationService::STATUS_PROMOTED;
        }
        
        $this->assign("authorized", $authorized);
        $this->assign("promoted", $promoted);
    }         
    
    public function getList( $params )
    {
        $idList = OW::getEventManager()->call("hotlist.get_id_list");
        if ( empty($idList) )
        {
            $this->assign("list", array());
            
            return;
        }
        
        $avatarList = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList, true, false);
        
        foreach ( $avatarList as $userId => $user )
        {
            $list[] = array(
                "userId" => $userId,
                "avatarUrl" => $user["src"]
            );
        }
        
        $this->assign("list", $list);
        $this->commonHandler($params);
        
        $actionInfo = OW::getEventManager()->call("usercredits.action_info", array(
            "pluginKey" => "hotlist",
            "action" => "add_to_list",
            "userId" => OW::getUser()->getId()
        ));
        
        if ( empty($actionInfo["price"]) || intval($actionInfo["price"]) > 0 )
        {
            $price = 0;
        }
        else
        {
            $price = intval($actionInfo["price"]) * -1;
        }
        
        $this->assign("creditsPrice", $price);
    }
    
    public function addToList( $params )
    {
        $userId = $params["userId"];
        
        $result = OW::getEventManager()->call("hotlist.add_to_list", array(
            "userId" => $userId
        ));
        
        $this->assign("result", $result["result"]);
        $this->assign("message", $result["message"]);
        $this->assign("buyCredits", $result["buyCredits"]);
        
        $this->commonHandler($params);
    }
    
    public function removeFromList( $params )
    {
        $userId = $params["userId"];
        
        $result = OW::getEventManager()->call("hotlist.remove_from_list", array(
            "userId" => $userId
        ));
        
        $this->assign("result", $result["result"]);
        $this->assign("message", $result["message"]);
        
        $this->commonHandler($params);
    }
}