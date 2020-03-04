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
class SKANDROID_ACTRL_Matches extends OW_ApiActionController
{
    const SORT_NEWEST = 'newest';
    const SORT_COMPATIBLE = 'compatible';

    const COUNT = 20;

    public function init()
    {
        parent::init();

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new ApiAccessException(ApiAccessException::TYPE_NOT_AUTHENTICATED);
        }
    }

    public function getList( $params )
    {
        $userId = !empty($params['userId']) ? $params['userId'] : OW::getUser()->getId();
        $page = !empty($params['page']) ? abs((int)$params['page']) : 1;
        $limit = !empty($params['limit']) ? abs((int)$params['limit']) : self::COUNT;
        $first = ($page - 1) * $limit;

        $countMatches = $this->getUserCountMatches($userId);

        if ( empty($countMatches) )
        {
            $this->assign('list', array());

            return;
        }

        $sort = (!empty($params['sort']) && $this->isAvailableSort($params['sort']))? $params['sort'] : $this->getDefaultSort();
        $matchList = $this->getUserMatches($userId, $sort, $first, $limit);

        if ( empty($matchList) )
        {
            $this->assign('list', array());

            return;
        }

        $userIdList = array();
        $compatibilityList = array();
        
        foreach ( $matchList as $item )
        {
            $userIdList[] = $item['id'];
            $compatibilityList[$item['id']] = $item['compatibility'];
        }

//        $userRoleList = array();
//
//        foreach ( BOL_AuthorizationService::getInstance()->getRoleListOfUsers($userIdList) as $role )
//        {
//            $userRoleList[$role['userId']] = array('label' => $role['label'], 'custom' => $role['custom']);
//        }

        $userData = array();
        $displayNameList = BOL_UserService::getInstance()->getDisplayNamesForList($userIdList);
        $questionsData = BOL_QuestionService::getInstance()->getQuestionData($userIdList, array('googlemap_location', 'birthdate'));
        
        foreach ( $questionsData as $_userId => $data )
        {
            $date = UTIL_DateTime::parseDate($data['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
            $userData[$_userId]['ages'] = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            $userData[$_userId]['displayName'] = $displayNameList[$_userId];
            $userData[$_userId]['location'] = empty($data['googlemap_location']['address']) ? '' : $data['googlemap_location']['address'];
        }

        $avatarList = array();
        $skadateService = SKADATE_BOL_Service::getInstance();

        foreach ( $skadateService->findAvatarListByUserIdList($userIdList) as $avatar )
        {
            $avatarList[$avatar->userId] = $skadateService->getAvatarUrl($avatar->userId, $avatar->hash);
        }

        foreach ( array_diff($userIdList, array_keys($avatarList)) as $_userId )
        {
            $avatarList[$_userId] = BOL_AvatarService::getInstance()->getAvatarUrl($_userId, 2);
        }
        
        $list = array();
        $authorizeMsg = '';
        $bookmarksList = $this->getUserBookmarkList($userId, $userIdList);
        $isAuthorized = $isSubscribe = OW::getUser()->isAuthorized('photo', 'view');

        if ( !$isAuthorized )
        {
            $status = SKANDROID_ABOL_Service::getInstance()->getAuthorizationActionStatus('photo', 'view');
            $isSubscribe = $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED;
            $authorizeMsg = $status['msg'];
        }

        foreach ( $userIdList as $userId )
        {
            $event = new OW_Event('photo.getMainAlbum', array('userId' => $userId));
            OW::getEventManager()->trigger($event);
            $album = $event->getData();

            $userPhotos = array();
            $photos = !empty($album['photoList']) ? $album['photoList'] : array();

            foreach ( $photos as $photo )
            {
                if ( $photo['status'] == PHOTO_BOL_PhotoDao::STATUS_APPROVED )
                {
                    $userPhotos[] = $photo['url']['main'];
                }
            }

            $list[] = array(
                'userId' => $userId,
                'isAuthorized' => $isAuthorized,
                'isSubscribe' => $isSubscribe,
                'displayName' => $userData[$userId]['displayName'],
//                'label' => $userRoleList[$userId]['label'],
//                'labelColor' => $userRoleList[$userId]['custom'],
                'compatibility' => $compatibilityList[$userId],
                'location' => $userData[$userId]['location'],
                'ages' => $userData[$userId]['ages'],
                'bookmarked' => !empty($bookmarksList[$userId]),
                'avatar' => !empty($avatarList[$userId]) ? $avatarList[$userId] : null,
                'photos' => $userPhotos,
                'authorizeMsg' => $authorizeMsg
            );
        }

        $event = new OW_Event(SKANDROID_ACLASS_EventHandler::USER_LIST_PREPARE_USER_DATA, array('listName' => 'match_list'), $list);
        OW_EventManager::getInstance()->trigger($event);

        $this->assign('list', $event->getData());
    }

    private  function getAvailableSort()
    {
        return array(
            self::SORT_NEWEST,
            self::SORT_COMPATIBLE
        );
    }

    private function isAvailableSort( $type )
    {
        return !empty($type) && in_array($type, $this->getAvailableSort());
    }

    private function getDefaultSort()
    {
        return self::SORT_NEWEST;
    }

    private function getUserCountMatches( $userId )
    {
        return (int)OW::getEventManager()->call('matchmaking.get_list_count', array(
            'userId' => $userId
        ));
    }

    private function getUserMatches( $userId, $sort, $first, $count )
    {
        return OW::getEventManager()->call('matchmaking.get_list', array(
            'userId' => $userId,
            'sort' => $sort,
            'first' => $first,
            'count' => $count
        ));
    }

    private function getUserBookmarkList( $userId, $idList )
    {
        return OW::getEventManager()->call('bookmarks.get_mark_list', array(
            'userId' => $userId,
            'idList' => $idList
        ));
    }
}
