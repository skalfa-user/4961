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
class SKANDROID_ACTRL_HotList extends OW_ApiActionController
{
    private function commonHandler( $params )
    {
        $this->assign('userAdded', OW::getEventManager()->call('hotlist.is_user_added', array(
            'userId' => OW::getUser()->getId()
        )));

        $authorized = OW::getUser()->isAuthorized('hotlist', 'add_to_list');
        $promoted = false;

        if ( !$authorized )
        {
            $status = SKANDROID_ABOL_Service::getInstance()->getAuthorizationActionStatus('hotlist', 'add_to_list');
            $promoted = $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED;
            $this->assign('authorizeMsg', $status['msg']);
            $this->assign('isCreditsEnable', OW::getPluginManager()->isPluginActive('usercredits'));
            $this->assign('isSubscribeEnable', OW::getPluginManager()->isPluginActive('membership'));
        }

        $this->assign('authorized', $authorized);
        $this->assign('promoted', $promoted);
    }

    public function getCount( array $params )
    {
        $count = OW::getEventManager()->call('hotlist.count');
        $this->assign('count', $count);
    }
    
    public function getList( $params )
    {
        $idList = OW::getEventManager()->call('hotlist.get_id_list');
        $avatarList = $list = array();
        $skadateService = SKADATE_BOL_Service::getInstance();
        $avatarService = BOL_AvatarService::getInstance();
        $defaultAvatar = $avatarService->getDefaultAvatarUrl(2);

        foreach ( $skadateService->findAvatarListByUserIdList($idList) as $avatar )
        {
            $avatarList[$avatar->userId] = $skadateService->getAvatarUrl($avatar->userId, $avatar->hash);
        }

        foreach ( array_diff($idList, array_keys($avatarList)) as $_userId )
        {
            $avatarList[$_userId] = $avatarService->getAvatarUrl($_userId, 2);
        }

        foreach ( $idList as $userId )
        {
            $list[] = array(
                'userId' => $userId,
                'avatar' => isset($avatarList[$userId]) ? $avatarList[$userId] : $defaultAvatar
            );
        }
        
        $this->assign('list', $list);
        $this->commonHandler($params);
    }
    
    public function addToList( array $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new ApiAccessException(ApiAccessException::TYPE_NOT_AUTHENTICATED);
        }

        $userId = OW::getUser()->getId();
        $result = OW::getEventManager()->call('hotlist.add_to_list', array(
            'userId' => $userId
        ));
        
        $this->assign('result', $result['result']);
        $this->assign('message', $result['message']);
        $this->assign('buyCredits', $result['buyCredits']);
        
        $this->commonHandler($params);
    }
    
    public function removeFromList( array $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new ApiAccessException(ApiAccessException::TYPE_NOT_AUTHENTICATED);
        }

        $userId = OW::getUser()->getId();
        $result = OW::getEventManager()->call('hotlist.remove_from_list', array(
            'userId' => $userId
        ));
        
        $this->assign('result', $result['result']);
        $this->assign('message', $result['message']);
        
        $this->commonHandler($params);
    }
}