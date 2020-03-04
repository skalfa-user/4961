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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.skadateios.api.controllers
 * @since 1.0
 */
class SKADATEIOS_ACTRL_Speedmatch extends OW_ApiActionController
{
    private static $basicNames = array();

    private static $priorityNames = array();

    const QUESTIONS_DISPLAY_LIMIT = 4;

    public function __construct()
    {
        parent::__construct();

        self::$basicNames = array(
            OW::getConfig()->getValue('base', 'display_name_question'),
            'birthdate',
            'sex',
            'googlemap_location'
        );

        self::$priorityNames = array(
            'match_sex',
            'aboutme',
            'relationship',
            'aboutmymatch'
        );
    }

    public function getUser( $params )
    {
        $viewerId = OW::getUser()->getId();

        // remove location on demo site
        if ( OW::getPluginManager()->isPluginActive("demoreset") && !empty($params['criteria']['googlemap_location']) )
        {
            unset($params['criteria']['googlemap_location']);
        }

        $users = OW::getEventManager()->call("speedmatch.suggest_users", array(
            "userId" => $viewerId,
            "criteria" => !empty($params["criteria"]) ? $params["criteria"] : null,
            "first" => empty($params["first"]) ? 0 : $params["first"],
            "count" => empty($params["count"]) ? 1 : $params["count"],
            "exclude" => array()
        ));

        if ( empty($users[0]) )
        {
            $this->assign('userId', null);

            return;
        }

        $userId = $users[0];

        $questionService = BOL_QuestionService::getInstance();
        $accountType = BOL_UserService::getInstance()->findUserById($userId)->accountType;

        $viewQuestionList = $questionService->findViewQuestionsForAccountType($accountType);

        $mainNames = array();
        $extraNames = array();
        foreach ( $viewQuestionList as $viewQuestion )
        {
            $name = $viewQuestion['name'];
            if ( in_array($name, self::$basicNames) )
            {
                continue;
            }
            if ( in_array($name, self::$priorityNames) )
            {
                $mainNames[] = $name;
            }
            else
            {
                $extraNames[] = $name;
            }
        }

        $viewNames = count($extraNames) ? array_merge($mainNames, $extraNames) : $mainNames;

        if ( count($viewNames) > self::QUESTIONS_DISPLAY_LIMIT )
        {
            $viewNames = array_slice($viewNames, 0, self::QUESTIONS_DISPLAY_LIMIT);
        }

        $viewQuestionList = BOL_UserService::getInstance()->getUserViewQuestions($userId, false, $viewNames);

        $questions = array();
        foreach ( $viewQuestionList['questions'] as $section )
        {
            foreach ( $section as $question )
            {
                $questions[$question['name']] = $question;
            }
        }

        $viewQuestions = array();
        $data = isset($viewQuestionList['data'][$userId]) ? $viewQuestionList['data'][$userId] : array();

        foreach ( $viewNames as $name )
        {
            if ( !isset($data[$name]) )
            {
                continue;
            }

            if ( is_array($data[$name]) )
            {
                $values = array();
                foreach ( $data[$name] as $val )
                {
                    $values[] = strip_tags($val);
                }
            }
            else
            {
                $values = strip_tags($data[$name]);
            }

            if ( empty($questions[$name]) )
            {
                continue;
            }
            $question = $questions[$name];

            $viewQuestions[] = array(
                'id' => $question['id'],
                'name' => $name,
                'label' => $questionService->getQuestionLang($name),
                'value' => $values,
                'section' => $question['sectionName'],
                'custom' => json_decode($question['custom'], true),
                'presentation' => $name == 'googlemap_location' ? $name : $question['presentation']
            );
        }

        $this->assign('questions', $viewQuestions);

        $basicQuestionList = BOL_UserService::getInstance()->getUserViewQuestions($userId, false, self::$basicNames);
        $viewBasic = $basicQuestionList['data'][$userId];
        if ( isset($viewBasic['realname']) )
        {
            $viewBasic['username'] = $viewBasic['realname'];
        }
        if ( isset($viewBasic['birthdate']) )
        {
            $viewBasic['birthdate'] = (int) $viewBasic['birthdate'];
        }
        if ( isset($viewBasic['sex']) )
        {
            $viewBasic['sex'] = reset($viewBasic['sex']);
        }
        $viewBasic['online'] = (bool) BOL_UserService::getInstance()->findOnlineUserById($userId);

        // compatibility
        if ( $userId != $viewerId )
        {
            $viewBasic['compatibility'] = OW::getEventManager()->call("matchmaking.get_compatibility", array(
                "firstUserId" => $viewerId,
                "secondUserId" => $userId
            ));
        }
        else
        {
            $viewBasic['compatibility'] = null;
        }

        $this->assign('basic', $viewBasic);

        $event = new OW_Event('photo.getMainAlbum', array('userId' => $userId));
        OW::getEventManager()->trigger($event);
        $album = $event->getData();

        $list = !empty($album['photoList']) ? $album['photoList'] : array();

        $photos = array();

        $bigAvatar = SKADATE_BOL_Service::getInstance()->findAvatarByUserId($userId);
        $avatarMainUrl = $bigAvatar
            ? SKADATE_BOL_Service::getInstance()->getAvatarUrl($userId, $bigAvatar->hash)
            : BOL_AvatarService::getInstance()->getAvatarUrl($userId, 2);

        if ( $avatarMainUrl )
        {
            $photo0 = SKADATEIOS_ACTRL_Photo::preparePhotoData(0, null);
            $photo0['mainUrl'] = $avatarMainUrl;
            $photo0['thumbUrl'] = $bigAvatar ? $avatarMainUrl : BOL_AvatarService::getInstance()->getAvatarUrl($userId);

            $photos[] = $photo0;
        }

        if ( $list )
        {
            foreach ( $list as $photo )
            {
                $photos[] = SKADATEIOS_ACTRL_Photo::preparePhotoData($photo['id'], $photo['hash'], $photo['dimension']);
            }
        }

        $this->assign('photos', $photos);
        $this->assign('hasAvatar', !empty($avatarMainUrl));

        $service = SKADATEIOS_ABOL_Service::getInstance();
        $auth = array(
            'base.view_profile' => $service->getAuthorizationActionStatus('base', 'view_profile'),
            'photo.view' => $service->getAuthorizationActionStatus('photo', 'view')
        );

        $this->assign('auth', $auth);

        $this->assign('userId', $userId);
    }

    public function likeUser( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['userId']) )
        {
            throw new ApiResponseErrorException();
        }

        $oppUserId = (int) $params['userId'];
        $service = SKADATE_BOL_Service::getInstance();

        $result = $service->addSpeedmatchRelation($userId, $oppUserId, 1);

        if ( $result )
        {
            $mutual = $service->isSpeedmatchRelationMutual($userId, $oppUserId);

            if ( $mutual )
            {
                $convId = $service->startSpeedmatchConversation($userId, $oppUserId);
                $this->assign('convId', $convId);
                
                $list = OW::getEventManager()->call("mailbox.get_chat_user_list", array(
                    "userId" => OW::getUser()->getId(),
                    "from" => 0,
                    "count" => 10
                ));

                $conversation = null;

                foreach ( $list as $item )
                {
                    if ( $item["conversationId"] == $convId )
                    {
                        $conversation = $item;
                    }
                }
                
                $this->assign("conversation", $conversation);

                $event = new OW_Event("speedmatch.after_match", array(
                    "opponentId" => $oppUserId,
                    "userId" => $userId,
                    "conversationId" => $conversation !== null ? $conversation["conversationId"] : null
                ));
                OW::getEventManager()->trigger($event);
                
            }
            $this->assign('mutual', $mutual);
        }
                

        $activeModes = OW::getEventManager()->call('mailbox.get_active_mode_list');
        $activeModes = empty($activeModes) ? array() : $activeModes;
        $this->assign('chatMode', in_array('chat', $activeModes) ? 1 : 0);
        
        $this->assign('result', $result);
    }

    public function skipUser( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['userId']) )
        {
            throw new ApiResponseErrorException();
        }

        $oppUserId = (int) $params['userId'];

        $result = SKADATE_BOL_Service::getInstance()->addSpeedmatchRelation($userId, $oppUserId, 0);

        $this->assign('result', $result);
    }

    public function getCriteria( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        $accTypes = BOL_QuestionService::getInstance()->findAllAccountTypes();
        $questionService = BOL_QuestionService::getInstance();

        $questionNames = array();
        $labels = array();

        if ( count($accTypes) > 1 )
        {
            $questionNames[] = 'sex';
            $labels['sex'] = 'show_me';
        }

        $questionNames[] = 'birthdate';
        $labels['birthdate'] = 'age';

        if ( OW::getPluginManager()->isPluginActive('googlelocation') )
        {
            $questionNames[] = 'googlemap_location';
            $labels['googlemap_location'] = OW::getConfig()->getValue('googlelocation', 'distance_units') == 'miles'
                ? 'miles_from_current_location'
                : 'kilometers_from_current_location';
        }

        $questions = $questionService->findQuestionByNameList($questionNames);
        $options = $questionService->findQuestionsValuesByQuestionNameList($questionNames);
        $data = $questionService->getQuestionData(array($userId), array('match_sex', 'match_age'));

        $criteria = array();
        foreach ( $questionNames as $name )
        {
            $question = $questions[$name];
            $array = array(
                'name' => $name,
                'label' => $labels[$name],
                'options' => self::formatOptionsForQuestion($name, $options),
                'custom' => json_decode($question->custom, true),
                'presentation' => $name == 'googlemap_location' ? $name : $question->presentation,
                'rawValue' => null
            );
            if ( $name == 'sex' )
            {
                $array['rawValue'] = isset($data[$userId]['match_sex']) ? $data[$userId]['match_sex'] : null;
            }
            else if ( $name == 'birthdate' )
            {
                $array['rawValue'] = isset($data[$userId]['match_age']) ? $data[$userId]['match_age'] : null;
            }

            $criteria[] = $array;
        }

        $this->assign('criteria', $criteria);
    }

    private static function formatOptionsForQuestion( $name, $allOptions )
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
}