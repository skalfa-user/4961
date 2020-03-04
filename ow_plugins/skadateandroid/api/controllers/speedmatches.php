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
class SKANDROID_ACTRL_Speedmatches extends OW_ApiActionController
{
    const COUNT_LIST = 20;
    const DEFAULT_DISTANCE = 50;
    const curuser = 8;

    private $baseQuestions;
    private $priorityQuestions;
    private $forbiddenQuestions;
    private $skadateService;

    public function __construct()
    {
        parent::__construct();

        $this->baseQuestions = array('birthdate', 'googlemap_location');
        $this->priorityQuestions = array('sex', 'match_sex', 'aboutme');
        $this->forbiddenQuestions = $this->baseQuestions + array('username', 'email', 'password', 'realname');

        $this->skadateService = SKADATE_BOL_Service::getInstance();
    }

    public function init()
    {
        parent::init();

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new ApiAccessException(ApiAccessException::TYPE_NOT_AUTHENTICATED);
        }
    }

    private function getSpeedmatchUserIdList( $userId, $first, $count, array $criteria, array $exclude )
    {
        return OW::getEventManager()->call('speedmatch.suggest_users',
                array(
                'userId' => $userId,
                'first' => $first,
                'count' => $count,
                'criteria' => $criteria,
                'exclude' => $exclude
        ));
    }

    private function getUserInfo( $userId, $question, $value )
    {
        $questionName = $question['name'];

        $event = new OW_Event('base.questions_field_get_label',
            array(
            'presentation' => $question['presentation'],
            'fieldName' => $questionName,
            'configs' => $question['custom'],
            'type' => 'view'
        ));
        OW::getEventManager()->trigger($event);

        $eventData = $event->getData();
        $questionLabel = !empty($eventData) ? $eventData : BOL_QuestionService::getInstance()->getQuestionLang($questionName);

        $event = new OW_Event('base.questions_field_get_value',
            array(
            'presentation' => $question['presentation'],
            'fieldName' => $questionName,
            'value' => $value,
            'questionInfo' => $question,
            'userId' => $userId
        ));
        OW::getEventManager()->trigger($event);

        $eventValue = $event->getData();
        $questionValue = '';

        if ( !empty($eventValue) )
        {
            $questionValue = $eventValue;
        }
        else
        {
            switch ( $question['presentation'] )
            {
                case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX:
                    $questionValue = OW::getLanguage()->text('base', (int) $value === 1 ? 'yes' : 'no');
                    break;
                case BOL_QuestionService::QUESTION_PRESENTATION_DATE:
                    $value = null;

                    switch ( $question['type'] )
                    {
                        case BOL_QuestionService::QUESTION_VALUE_TYPE_DATETIME:
                            $date = UTIL_DateTime::parseDate($value, UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                            if ( isset($date) )
                            {
                                $value = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                            }
                            break;
                        case BOL_QuestionService::QUESTION_VALUE_TYPE_SELECT:
                            $value = (int) $value;
                            break;
                    }

                    $questionValue = date(OW::getConfig()->getValue('base', 'date_field_format') === 'dmy' ? 'd/m/Y' : 'm/d/Y',
                        $value);
                    break;
                case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE:
                    $date = UTIL_DateTime::parseDate($value, UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                    $questionValue = UTIL_DateTime::formatBirthdate($date['year'], $date['month'], $date['day']);
                    break;
                case BOL_QuestionService::QUESTION_PRESENTATION_AGE:
                    $date = UTIL_DateTime::parseDate($value, UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                    $questionValue = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']) . " " . OW::getLanguage()->text('base',
                            'questions_age_year_old');
                    break;
                case BOL_QuestionService::QUESTION_PRESENTATION_RANGE:
                    $range = explode('-', $value);
                    $questionValue = OW::getLanguage()->text('base', 'form_element_from') . " " . $range[0] . " " . OW::getLanguage()->text('base',
                            'form_element_to') . " " . $range[1];
                    break;
                case BOL_QuestionService::QUESTION_PRESENTATION_SELECT:
                case BOL_QuestionService::QUESTION_PRESENTATION_RADIO:
                case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX:
                    $multicheckboxValue = (int) $value;
                    $parentName = $question['name'];

                    if ( !empty($question['parent']) )
                    {
                        $parent = BOL_QuestionService::getInstance()->findQuestionByName($question['parent']);

                        if ( !empty($parent) )
                        {
                            $parentName = $parent->name;
                        }
                    }

                    static $values = array();

                    if ( !isset($values[$parentName]) )
                    {
                        $values[$parentName] = BOL_QuestionService::getInstance()->findQuestionValues($parentName);
                    }

                    $_value = array();

                    foreach ( $values[$parentName] as $val )
                    {
                        if ( ((int) $val->value) & $multicheckboxValue )
                        {
                            $_value[] = BOL_QuestionService::getInstance()->getQuestionValueLang($val->questionName,
                                $val->value);
                        }
                    }

                    $questionValue = $_value;
                    break;
                default:
                    $questionValue = strip_tags(trim((string) $value));
                    break;
            }
        }

        return array(
            'label' => $questionLabel,
            'value' => $questionValue
        );
    }

    private function getDefaultFilter( $userId )
    {
        $questionNames = array('match_sex', 'match_age', 'googlemap_location');
        $data = BOL_QuestionService::getInstance()->getQuestionData(array($userId), $questionNames);
        $data = $data[$userId];
        $result = array('filter' => array(), 'api' => array());

        if ( !empty($data['match_sex']) )
        {
            $result['filter']['sex'] = $data['match_sex'];
            $question = BOL_QuestionService::getInstance()->findQuestionByName('match_sex');
            $values = array();

            foreach ( BOL_QuestionService::getInstance()->findQuestionValues('match_sex') as $value )
            {
                $values[] = array('id' => $value->value, 'label' => BOL_QuestionService::getInstance()->getQuestionValueLang($value->questionName,
                        $value->value));
            }

            $filter = array();

            if ( in_array($question->presentation,
                    array(
                    BOL_QuestionService::QUESTION_PRESENTATION_SELECT,
                    BOL_QuestionService::QUESTION_PRESENTATION_RADIO,
                    BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX)) )
            {
                foreach ( $values as $val )
                {
                    if ( $val['id'] & $data['match_sex'] )
                    {
                        $filter[] = $val['id'];
                    }
                }
            }
            else
            {
                $filter[] = $data['match_sex'];
            }

            $result['api']['match_sex'] = $filter;
            $result['api']['match_sex_data'] = $values;
        }

        if ( !empty($data['match_age']) )
        {
            $result['api']['match_age'] = $result['filter']['birthdate'] = $data['match_age'];
        }

        if ( !empty($data['googlemap_location']) )
        {
            $googleFilter = $data['googlemap_location'];
            $googleFilter['distance'] = empty($googleFilter['distance']) ? self::DEFAULT_DISTANCE : $googleFilter['distance'];
            $result['api']['googlemap_location'] = $result['filter']['googlemap_location'] = $googleFilter;
        }

        return $result;
    }

    private function isValidFilter( $filter )
    {
        return !empty($filter) && !empty($filter['googlemap_location']) && count(array_intersect(array_keys($filter['googlemap_location']),
                    array('latitude', 'longitude', 'distance'))) === 3;
    }

    private function getValidFilter( $filter, $userId = null )
    {
        $questions = array();

        if ( empty($filter['googlemap_location']) || empty($filter['googlemap_location']['longitude']) || empty($filter['googlemap_location']['latitude']) )
        {
            $questions[] = 'googlemap_location';
        }

        if ( empty($filter['match_sex']) )
        {
            $questions[] = 'match_sex';
        }
        else
        {
            $filter['sex'] = array_sum($filter['match_sex']);
            unset($filter['match_sex']);
        }

        if ( empty($filter['match_age']) )
        {
            $questions[] = 'match_age';
        }
        else
        {
            $filter['birthdate'] = $filter['match_age'];
            unset($filter['match_age']);
        }

        if ( empty($questions) )
        {
            unset($filter['match_sex_data']);
            return $filter;
        }

        $userId = empty($userId) ? OW::getUser()->getId() : $userId; //////////////////////////////////// TODO
        $data = BOL_QuestionService::getInstance()->getQuestionData(array($userId), $questions);
        $data = $data[$userId];

        // getValue
        $questionList = BOL_QuestionService::getInstance()->findQuestionByNameList(['birthdate']);
        $max = $min = null;
        if ( !empty($questionList) )
        {
            if ( !empty($questionList['birthdate']->custom) )
            {
                $configs = BOL_QuestionService::getInstance()->getQuestionConfig($questionList['birthdate']->custom, 'year_range');

                $max = !empty($configs['from']) ? date("Y") - (int) $configs['from'] : null;
                $min = !empty($configs['to']) ? date("Y") - (int) $configs['to'] : null;
            }
        }

        $result = array();
        $result['sex'] = !empty($filter['match_sex']) ? $filter['match_sex'] : !empty($filter['sex']) ? $filter['sex'] : $data['match_sex'];
        $result['birthdate'] = !empty($filter['match_age']) ? $filter['match_age'] : !empty($filter['birthdate']) ? $filter['birthdate'] : $data['match_age'];
        if ( !is_null($min) && !is_null($max))
        {
            if ( !empty($result['birthdate']) )
            {
                list($from, $to) = explode('-', $result['birthdate']);

                if ( $from < $min ) {
                    $from = $min;
                }

                if ( $to > $max )
                {
                    $to = $max;
                }

                $result['birthdate'] = $from . '-' . $to;
            }

            $result['defaultMatchAge'] = $min . '-' . $max;

            unset($from, $to, $min, $max, $configs);
        }

        if ( isset($data['googlemap_location']) || isset($filter['googlemap_location']) )
        {
            $result['googlemap_location']['distance'] = empty($filter['googlemap_location']['distance']) ? self::DEFAULT_DISTANCE : $filter['googlemap_location']['distance'];
            $result['googlemap_location']['longitude'] = empty($filter['googlemap_location']['longitude']) ? $data['googlemap_location']['longitude'] : $filter['googlemap_location']['longitude'];
            $result['googlemap_location']['latitude'] = empty($filter['googlemap_location']['latitude']) ? $data['googlemap_location']['latitude'] : $filter['googlemap_location']['latitude'];
        }

        return $result;
    }

    private function isAuthorizedForRole()
    {
        $groupId = BOL_AuthorizationService::getInstance()->findGroupIdByName('photo');
        $photoAction = BOL_AuthorizationActionDao::getInstance()->findAction('view', $groupId);

        foreach ( BOL_AuthorizationService::getInstance()->getRoleList() as $role )
        {
            if ( BOL_AuthorizationPermissionDao::getInstance()->findByRoleIdAndActionId($role->id, $photoAction->id) !== null )
            {
                return true;
            }
        }

        return false;
    }

    public function getList( $params )
    {
        $viewerId = !empty($params['userId']) ? $params['userId'] : OW::getUser()->getId();
        $first = empty($params['first']) ? 0 : $params['first'];
        $count = empty($params['count']) ? self::COUNT_LIST : $params['count'];
        $exclude = empty($params['exclude']) ? array() : json_decode($params['exclude']);
        $filter = empty($params['filter']) ? array() : json_decode($params['filter'], true);
        $validFilter = $this->getValidFilter($filter);

        $status = SKANDROID_ABOL_Service::getInstance()->getAuthorizationActionStatus('base', 'view_profile');

        if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
        {
            $this->assign('authorized', false);
            $this->assign('promoted', $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED);
            $this->assign('authorizeMsg', $status['msg']);

            return;
        }

        $this->assign('authorized', true);

        if ( empty($filter['match_sex_data']) )
        {
            $question = BOL_QuestionService::getInstance()->findQuestionByName('match_sex');
            $values = $_filter = array();

            foreach ( BOL_QuestionService::getInstance()->findQuestionValues('match_sex') as $value )
            {
                $values[] = array('id' => $value->value, 'label' => BOL_QuestionService::getInstance()->getQuestionValueLang($value->questionName,
                        $value->value));
            }

            if ( in_array($question->presentation,
                    array(
                    BOL_QuestionService::QUESTION_PRESENTATION_SELECT,
                    BOL_QuestionService::QUESTION_PRESENTATION_RADIO,
                    BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX)) )
            {
                foreach ( $values as $val )
                {
                    if ( $val['id'] & $validFilter['sex'] )
                    {
                        $_filter[] = $val['id'];
                    }
                }
            }
            else
            {
                $_filter[] = $validFilter['match_sex'];
            }

            $filter['match_sex'] = $_filter;
            $filter['match_sex_data'] = $values;
        }

        if ( isset($validFilter['googlemap_location']) )
        {
            $filter['googlemap_location'] = $validFilter['googlemap_location'];
        }

        $filter['match_age'] = $validFilter['birthdate'];

        if ( OW::getPluginManager()->isPluginActive("googlelocation") )
        {
            $filter['distanceUnits'] = GOOGLELOCATION_BOL_LocationService::getInstance()->getDistanseUnits();
        }

        if ( !empty($validFilter['defaultMatchAge']) )
        {
            $filter['defaultMatchAge'] = $validFilter['defaultMatchAge'];
        }

        $this->assign('filter', $filter);

        // remove location on demo site
        if ( OW::getPluginManager()->isPluginActive("demoreset") && !empty($validFilter['googlemap_location']) )
        {
            unset($validFilter['googlemap_location']);
        }

        $userIdList = $this->getSpeedmatchUserIdList($viewerId, $first, $count, $validFilter, $exclude);

        if ( empty($userIdList) )
        {
            $this->assign('list', array());

            return;
        }

        $userService = BOL_UserService::getInstance();
        $questionService = BOL_QuestionService::getInstance();

        $userList = $accountTypes = $compatibility = array();

        foreach ( $userService->findUserListByIdList($userIdList) as $user )
        {
            $userList[$user->id] = $user;
            $accountTypes[] = $user->accountType;
        }

        if ( ($_compatibility = OW::getEventManager()->call('matchmaking.get_compatibility_for_list',
            array('userId' => $viewerId, 'idList' => $userIdList)) ) )
        {
            foreach ( $_compatibility as $userId => $c )
            {
                $compatibility[$userId] = (int) $c;
            }
        }

        $accountTypes = array_unique($accountTypes);
        $totalQuestions = $accountTypeQuestions = $matchQuestions = array();

        foreach ( $accountTypes as $accountType )
        {
            $questionsForAccountType = $questionService->findViewQuestionsForAccountType($accountType);
            $questions = array();

            foreach ( $this->priorityQuestions as $pq )
            {
                foreach ( $questionsForAccountType as $qfa )
                {
                    if ( $qfa['name'] == $pq )
                    {
                        $questions[] = $qfa;
                        $this->forbiddenQuestions[] = $qfa['name'];
                        $matchQuestions[] = $qfa['name'];

                        break;
                    }
                }
            }

            foreach ( $questionsForAccountType as $question )
            {
                if ( $question['sectionName'] == 'about_my_match' )
                {
                    $questions[] = $question;
                    $this->forbiddenQuestions[] = $question['name'];

                    if ( $question['name'] == 'relationship' )
                    {
                        $matchQuestions[] = 'relationship';
                    }
                }
            }

            $questionsForAccountType = array_filter($questionsForAccountType, array($this, 'filterQuestionForAccount'));
            $questionsForAccountType = array_merge($questions, $questionsForAccountType);
            $accountTypeQuestions[$accountType] = $questionsForAccountType;
            $totalQuestions = array_merge($totalQuestions, $accountTypeQuestions[$accountType]);
        }

        $totalQuestions = array_unique(array_map(array($this, 'filterTotalQuestion'), $totalQuestions));

        $displayNameList = $userService->getDisplayNamesForList($userIdList);
        $avatarList = array();
        $skadateService = SKADATE_BOL_Service::getInstance();

        foreach ( $skadateService->findAvatarListByUserIdList($userIdList) as $avatar )
        {
            $avatarList[$avatar->userId] = $skadateService->getAvatarUrl($avatar->userId, $avatar->hash);
        }

        $avatarService = BOL_AvatarService::getInstance();
        $avatarList += $avatarService->getAvatarsUrlList(array_diff($userIdList, array_keys($avatarList)), 2);

        $avatarList = array_filter($avatarList, array($this, 'filterAvatarList'));

        $onlineStatus = BOL_UserService::getInstance()->findOnlineStatusForUserList($userIdList);

        $questionsData = $questionService->getQuestionData($userIdList,
            array_merge($totalQuestions, $this->baseQuestions));
        $userData = array();
        $authorizeMsg = '';

        $isAuthorized = $isSubscribe = OW::getUser()->isAuthorized('photo', 'view');

        if ( !$isAuthorized )
        {
            $status = SKANDROID_ABOL_Service::getInstance()->getAuthorizationActionStatus('photo', 'view');
            $isSubscribe = $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED;
            $authorizeMsg = $status['msg'];
        }

        foreach ( $questionsData as $userId => $data )
        {
            $user = $userPhotos = array();

            if ( $isAuthorized )
            {
                $event = new OW_Event('photo.getMainAlbum', array('userId' => $userId));
                OW::getEventManager()->trigger($event);
                $album = $event->getData();


                if ( !empty($album['photoList']) )
                {
                    foreach ( $album['photoList'] as $photo )
                    {
                        if ( $photo['status'] == PHOTO_BOL_PhotoDao::STATUS_APPROVED )
                        {
                            $userPhotos[] = $photo['url']['main'];
                        }
                    }
                }
            }

            $user['userId'] = $userId;
            $user['isAuthorized'] = $isAuthorized;
            $user['isSubscribe'] = $isSubscribe;
            $user['authorizeMsg'] = $authorizeMsg;
            $user['isOnline'] = !empty($onlineStatus[$userId]);
            $user['displayName'] = $displayNameList[$userId];
            $user['location'] = empty($data['googlemap_location']['address']) ? '' : $data['googlemap_location']['address'];

            $date = UTIL_DateTime::parseDate($data['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
            $user['ages'] = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            $user['avatar'] = !empty($avatarList[$userId]) ? $avatarList[$userId] : null;
            $user['photos'] = $userPhotos;
            $user['compatibility'] = isset($compatibility[$userId]) ? $compatibility[$userId] : null;
            $user['info'] = array();
            $f = array();

            foreach ( $accountTypeQuestions[$userList[$userId]->accountType] as $question )
            {
                if ( $question['name'] == 'relationship' && array_search('relationship', $matchQuestions) !== false )
                {
                    continue;
                }

                $questionName = $question['name'];

                if ( empty($data[$questionName]) || array_search($questionName, $f) !== false )
                {
                    continue;
                }

                $f[] = $questionName;

                $user['info'][] = $this->getUserInfo($userId, $question, $data[$questionName]);
            }

            $userData[] = $user;
        }

        $this->assign('list', $userData);

        $this->assign('list', $userData);
    }

    public function filterQuestionForAccount( $question )
    {
        return array_search($question['name'], $this->forbiddenQuestions) === false;
    }

    public function filterTotalQuestion( $question )
    {
        return $question['name'];
    }

    public function filterAvatarList( $avatar )
    {
        static $defAvatarUrl = null;

        if ( $defAvatarUrl === null )
        {
            $defAvatarUrl = BOL_AvatarService::getInstance()->getDefaultAvatarUrl(2);
        }

        return strcasecmp($avatar, $defAvatarUrl) !== 0;
    }

    public function likeUser( $params )
    {
        $userId = OW::getUser()->getId();

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

                $list = OW::getEventManager()->call('mailbox.get_chat_user_list',
                    array(
                    'userId' => $userId,
                    'count' => 10
                ));

                foreach ( $list as $conv )
                {
                    if ( $conv['conversationId'] == $convId )
                    {
                        $conversationItem = $conv;

                        break;
                    }
                }

                $activeModes = OW::getEventManager()->call('mailbox.get_active_mode_list');

                if ( count($activeModes) === 1 && in_array('mail', $activeModes) )
                {
                    $list = SKANDROID_ABOL_MailboxService::getInstance()->prepareConversationList(array($conversationItem));
                    $this->assign('conversation', $list[0]);
                }
                $event = new OW_Event("speedmatch.after_match",
                    array(
                    "opponentId" => $oppUserId,
                    "userId" => OW::getUser()->getId(),
                    "conversationId" => $conversationItem["conversationId"]
                ));
                OW::getEventManager()->trigger($event);
            }

            $this->assign('mutual', $mutual);
        }

        $this->assign('result', $result);
    }

    public function skipUser( $params )
    {
        $userId = OW::getUser()->getId();

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
            $labels['sex'] = 'Show me'; // TODO: use lang
        }

        $questionNames[] = 'birthdate';
        $labels['birthdate'] = 'Age'; // TODO: use lang

        if ( OW::getPluginManager()->isPluginActive('googlelocation') )
        {
            $questionNames[] = 'googlemap_location';
            $labels['googlemap_location'] = 'Miles from current location'; // TODO: use lang
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
                'options' => SKANDROID_ABOL_Service::getInstance()->formatOptionsForQuestion($name, $options),
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
}
