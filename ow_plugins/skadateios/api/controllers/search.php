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
class SKADATEIOS_ACTRL_Search extends OW_ApiActionController
{
    private function convertQuestionValue( $presentation, $value, $name )
    {
        if ( $name == "googlemap_location" && !empty($value["useCurrent"]))
        {
            $value = array(
                'distance' => !empty($value['distance']) ? $value['distance'] : 0,
                'address' => "",
                'latitude' => $value['latitude'],
                'longitude' => $value['longitude'],
                'northEastLat' => $value['latitude'],
                'northEastLng' => $value['longitude'],
                'southWestLat' => $value['latitude'],
                'southWestLng' => $value['longitude']
            );
            
            $value["json"] = json_encode($value);
        }
        
        switch ($presentation)
        {
            case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE:
            case BOL_QuestionService::QUESTION_PRESENTATION_AGE:
                list($from, $to) = explode("-", $value);
                
                return array(
                    "from" => $from,
                    "to" => $to
                );
            default:
                return $value;
        }
    }
    
    public function getList( $params )
    {
        $service = SKADATEIOS_ABOL_Service::getInstance();
        $auth = array(
            'photo.view' => $service->getAuthorizationActionStatus('photo', 'view'),
            'base.search_users' => $service->getAuthorizationActionStatus('base', 'search_users')
        );

        $this->assign('auth', $auth);
        
        if ( $auth["base.search_users"]["status"] != BOL_AuthorizationService::STATUS_AVAILABLE )
        {
            $this->assign("list", array());
            $this->assign("total", 0);
            
            return;
        }
        
        $_criteriaList = array_filter($params["criteriaList"]);
        
        $userId = OW::getUser()->getId();
        
        $userInfo = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array("sex"));
        $_criteriaList["sex"] = !empty($userInfo[$userId]["sex"]) ? $userInfo[$userId]["sex"] : null;
        
        $questionList = BOL_QuestionService::getInstance()->findQuestionByNameList(array_keys($_criteriaList));
                
        $criteriaList = array();
        foreach ( $_criteriaList as $questionName => $questionValue )
        {
            if ( empty($questionList[$questionName]) )
            {
                continue;
            }
            
            $criteriaList[$questionName] = $this->convertQuestionValue($questionList[$questionName]->presentation, $questionValue, $questionName);
        }
        
        $idList = OW::getEventManager()->call("usearch.get_user_id_list", array(
            "criterias" => $criteriaList,
            "limit" => array($params["first"], $params["count"])
        ));
        
        $idList = empty($idList) ? array() : $idList;
        
        //$idList = BOL_UserService::getInstance()->findUserIdListByQuestionValues($criteriaList, $params["first"], $params["count"]);
        //$total = BOL_UserService::getInstance()->countUsersByQuestionValues($params["criteriaList"]);
        $total = 150; // for increase performance on search result page
        
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
                "photos" => empty($photoList[$userId]) ? array() : $photoList[$userId],
                "avatar" => $avatarList[$userId],
                "name" => empty($userData[$userId]["title"]) ? "" : $userData[$userId]["title"],
                "label" => $userData[$userId]["label"],
                "labelColor" => $userData[$userId]["labelColor"],
                "location" => empty($userData[$userId]["location"]) ? "" : $userData[$userId]["location"],
                "ages" => $userData[$userId]["ages"],
                "bookmarked" => !empty($bookmarksList[$userId])
            );
        }

        $event = new OW_Event(SKADATEIOS_ACLASS_EventHandler::USER_LIST_PREPARE_USER_DATA, array('listName' => 'match_list'), $list);
        OW_EventManager::getInstance()->trigger($event);

        $this->assign("list", $event->getData());
        $this->assign("total", $total);
        
        $allowSendMessage = OW::getPluginManager()->isPluginActive('mailbox');
        
        $this->assign("actions", array(
            "bookmark" => OW::getPluginManager()->isPluginActive('bookmarks'),
            "message" => $allowSendMessage,
            "wink" => OW::getPluginManager()->isPluginActive('winks'),
        ));
        
        BOL_AuthorizationService::getInstance()->trackAction("base", "search_users");
        
        $mailboxModes = OW::getEventManager()->call('mailbox.get_active_mode_list');
        $this->assign("mailboxModes", empty($mailboxModes) ? array() : $mailboxModes);
    }
}