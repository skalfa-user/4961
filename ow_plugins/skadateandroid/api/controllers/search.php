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
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.skandroid.api.controllers
 * @since 1.0
 */
class SKANDROID_ACTRL_Search extends OW_ApiActionController
{
    private function convertQuestionValue( $presentation, $value )
    {
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
        $service = SKANDROID_ABOL_Service::getInstance();
        $auth = array(
            'photo_view' => $service->getAuthorizationActionStatus('photo', 'view'),
            'base_search_users' => $service->getAuthorizationActionStatus('base', 'search_users')
        );

        $this->assign('auth', $auth);

        if ( $auth["base_search_users"]["status"] != BOL_AuthorizationService::STATUS_AVAILABLE )
        {
            $this->assign("list", array());
            $this->assign("total", 0);

            return;
        }

        $_criteriaList = array_filter(json_decode($params["criteriaList"], true));

        $userId = OW::getUser()->getId();

        $userInfo = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array("sex", 'match_sex'));
        $_criteriaList["sex"] = !empty($userInfo[$userId]["sex"]) ? $userInfo[$userId]["sex"] : null;

        if ( OW::getPluginManager()->isPluginActive('googlelocation') )
        {
            $location = null;
            $json = null;

            if( !empty($_criteriaList["current_location"]) && $_criteriaList["current_location"] != "false" && !empty($_criteriaList["current_location_address"]) )
            {
                $location = !empty($_criteriaList["current_location_address"]) ? json_decode($_criteriaList["current_location_address"], true) : null;
                $json = $_criteriaList["current_location_address"];
            }
            else if ( !empty($_criteriaList["custom_location"]) )
            {

                $location = !empty($_criteriaList["custom_location"]) ? json_decode($_criteriaList["custom_location"], true) : null;
                $json = $_criteriaList["custom_location"];
            }

            if ( !empty($location) )
            {
                $value = array(
                    'distance' => !empty($_criteriaList['distance']) ? $_criteriaList['distance'] : 0,
                    'address' => $location['address'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'northEastLat' => $location['northEastLat'],
                    'northEastLng' => $location['northEastLng'],
                    'southWestLat' => $location['southWestLat'],
                    'southWestLng' => $location['southWestLng'],
                    'json' => $json
                );

                $_criteriaList['googlemap_location'] = $value;
            }
        }

        unset($_criteriaList["current_location"]);
        unset($_criteriaList["current_location_address"]);
        unset($_criteriaList["custom_location"]);

        $questionList = BOL_QuestionService::getInstance()->findQuestionByNameList(array_keys($_criteriaList));

        $criteriaList = array();
        foreach ( $_criteriaList as $questionName => $questionValue )
        {
            if ( empty($questionList[$questionName]) )
            {
                continue;
            }

            $criteriaList[$questionName] = $this->convertQuestionValue($questionList[$questionName]->presentation, $questionValue);
        }

        if ( empty($criteriaList['match_sex']) && !empty($userInfo[$userId]["match_sex"]) )
        {
            $criteriaList['match_sex'] = $userInfo[$userId]["match_sex"];
        }

        $idList = OW::getEventManager()->call("usearch.get_user_id_list_for_android", array(
            "criterias" => $criteriaList,
            "limit" => array($params["first"], $params["count"])
        ));

        $idList = empty($idList) ? array() : $idList;

        $userData = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList, false, false, true, true);
        $questionsData = BOL_QuestionService::getInstance()->getQuestionData($idList, array("googlemap_location", "birthdate"));
        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($idList);

        foreach ( $questionsData as $userId => $data )
        {
            if ( !empty($data['birthdate']) )
            {
                $date = UTIL_DateTime::parseDate($data['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                $userData[$userId]["ages"] = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            }

            $userData[$userId]["location"] = empty($data["googlemap_location"]["address"]) ? null : $data["googlemap_location"]["address"];
        }

        $photoList = array();
        $avatars = array();

        $bigAvatarList = SKADATE_BOL_Service::getInstance()->findAvatarListByUserIdList($idList);

        foreach ( $idList as $userId )
        {
            $bigAvatar = !empty($bigAvatarList[$userId]) ? $bigAvatarList[$userId] : null ;

            $event = new OW_Event('photo.getMainAlbum', array('userId' => $userId));
            OW::getEventManager()->trigger($event);
            $album = $event->getData();

            $photos = !empty($album['photoList']) ? $album['photoList'] : array();

            if ( $bigAvatar )
            {
                $avatars[$userId] = SKADATE_BOL_Service::getInstance()->getAvatarUrl($userId, $bigAvatar->hash);
            }
            else
            {
                $avatars[$userId] = BOL_AvatarService::getInstance()->getAvatarUrl($userId, 2);

                if( $avatars[$userId] == BOL_AvatarService::getInstance()->getDefaultAvatarUrl(2) )
                {
                    unset($avatars[$userId]);
                }
            }

            foreach ( $photos as $photo )
            {
                if ( $photo['status'] == PHOTO_BOL_PhotoDao::STATUS_APPROVED )
                {
                    $photoList[$userId][] = array(
                        "src" => $photo["url"]["main"]
                    );
                }
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
                "avatar" => !empty($avatars[$userId]) ? $avatars[$userId] : "",
                "photos" => empty($photoList[$userId]) ? array() : $photoList[$userId],
                "name" => empty($userData[$userId]["title"]) ? "" : $userData[$userId]["title"],
                "displayName" => !empty($displayNames[$userId]) ? $displayNames[$userId] : "",
                "label" => $userData[$userId]["label"],
                "labelColor" => $userData[$userId]["labelColor"],
                "location" => empty($userData[$userId]["location"]) ? "" : $userData[$userId]["location"],
                "ages" => empty($userData[$userId]["ages"]) ? 0 : $userData[$userId]["ages"],
                "bookmarked" => !empty($bookmarksList[$userId])
            );
        }

        $event = new OW_Event(SKANDROID_ACLASS_EventHandler::USER_LIST_PREPARE_USER_DATA, array('listName' => 'search_result'), $list);
        OW_EventManager::getInstance()->trigger($event);

        $this->assign("list", $event->getData());

        BOL_AuthorizationService::getInstance()->trackAction("base", "search_users");
    }
}