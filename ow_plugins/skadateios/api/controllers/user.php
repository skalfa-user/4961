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
class SKADATEIOS_ACTRL_User extends OW_ApiActionController
{
    const PREFERENCE_LIST_OF_CHANGES = BASE_CTRL_Edit::PREFERENCE_LIST_OF_CHANGES;
    
    private static $basicNames = array();
    private static $searchBasicNames = array();
    private static $searchFilledNames = array();
    private static $isGoogleMapLocationInstalled = false;

    public function __construct()
    {
        parent::__construct();

        $pm = OW::getPluginManager();

        self::$basicNames[] = OW::getConfig()->getValue('base', 'display_name_question');
        self::$basicNames[] = 'birthdate';
        self::$basicNames[] = 'sex';
        if ( $pm->isPluginActive('googlelocation') )
        {
            self::$basicNames[] = 'googlemap_location';
        }

        self::$searchFilledNames[] = 'sex';
        if ( $pm->isPluginActive('googlelocation') )
        {
            self::$searchFilledNames[] = 'googlemap_location';
        }
        self::$searchFilledNames[] = 'relationship';
        self::$searchFilledNames[] = 'birthdate';

        self::$searchBasicNames[] = 'sex';
        self::$searchBasicNames[] = 'match_sex';

        if ( $pm->isPluginActive('googlelocation') )
        {
            self::$searchBasicNames[] = 'googlemap_location';
            self::$isGoogleMapLocationInstalled = true;
        }

        self::$searchBasicNames[] = 'relationship';
        self::$searchBasicNames[] = 'birthdate';
    }

    public function authenticate($params)
    {
        $token = null;

        if ( !OW::getUser()->isAuthenticated() )
        {
            $params["username"] = empty($params["username"]) ? "" : $params["username"];
            $params["password"] = empty($params["password"]) ? "" : $params["password"];

            $result = OW::getUser()->authenticate(new BASE_CLASS_StandardAuth($params["username"], $params["password"]));

            if ( !$result->isValid() )
            {
                $messages = $result->getMessages();

                throw new ApiResponseErrorException(array(
                    "message" => empty($messages) ? "" : $messages[0]
                ));
            }

            $token = OW_Auth::getInstance()->getAuthenticator()->getId();
        }

        $userId = OW::getUser()->getId();

        $avatarService = BOL_AvatarService::getInstance();
        $userService = BOL_UserService::getInstance();
        $service = SKADATEIOS_ABOL_Service::getInstance();

        $_questionsData = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array(
            "sex", "birthdate"
        ));

        $questionsData = $_questionsData[$userId];
        $date = UTIL_DateTime::parseDate($questionsData['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
        $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);

        $userData = array(
            "userId" => $userId,
            "displayName" => $userService->getDisplayName($userId),
            "avatar" => SKADATEIOS_ABOL_Service::getInstance()->dataForUserAvatar($userId),
            "suspended" => BOL_UserService::getInstance()->isSuspended($userId),
            "approved" => BOL_UserService::getInstance()->isApproved($userId),
            "sex" => $questionsData["sex"],
            "age" => $age,
            "birthdate" => $date
        );

        $this->assign("user", $userData);
        $this->assign("token", $token);

        $mainMenu = $service->getMenu($userId, 'main');
        $this->assign("mainMenu", $mainMenu);

        $bottomMenu = $service->getMenu($userId, 'bottom');
        $this->assign("bottomMenu", $bottomMenu);

        $this->assign('newCounter', $service->getNewItemsCount($mainMenu));
    }

    public function getInfo( $params, $pathParams )
    {
        $userId = $pathParams["userId"];

        $avatarService = BOL_AvatarService::getInstance();
        $userService = BOL_UserService::getInstance();

        $this->assign("avatar", array(
            "url" => $avatarService->getAvatarUrl($userId)
        ));

        $this->assign("displayName", $userService->getDisplayName($userId));
    }

    public function signout()
    {
        OW::getUser()->logout();
    }

    public function getQuestions( $params )
    {
        if ( empty($params['userId']) )
        {
            throw new ApiResponseErrorException();
        }

        $userId = (int) $params['userId'];
        $user = BOL_UserService::getInstance()->findUserById($userId);

        if ( !$user )
        {
            throw new ApiResponseErrorException();
        }

        $service = SKADATEIOS_ABOL_Service::getInstance();
        $questionService = BOL_QuestionService::getInstance();
        $accountType = OW::getUser()->getUserObject()->accountType;

        $viewQuestionList = $questionService->findViewQuestionsForAccountType($accountType);
        $viewNames = array();

        foreach ( $viewQuestionList as $viewQuestion )
        {
            $viewNames[] = $viewQuestion['name'];
        }

        $viewQuestionList = BOL_UserService::getInstance()->getUserViewQuestions($userId, false, $viewNames);
        $viewSections = array();
        $sortedSections = $questionService->findSortedSectionList();

        foreach ( $viewQuestionList['questions'] as $sectionName => $section )
        {
            if ( $sectionName == 'location' )
            {
                continue;
            }

            $order = 0;
            foreach ( $sortedSections as $sorted )
            {
                if ( $sorted->name == $sectionName )
                {
                    $order = $sorted->sortOrder;
                }
            }
            $viewSections[] = array('order' => $order, 'name' => $sectionName, 'label' => $questionService->getSectionLang($sectionName));
        }

        usort($viewSections, array('SKADATEIOS_ACTRL_User', 'sortByOrder'));

        $viewQuestions = array();
        $viewBasic = array();
        $data = $viewQuestionList['data'][$userId];

        foreach ( $viewQuestionList['questions'] as $sectName => $section )
        {
            $sectionQuestions = array();

            foreach ( $section as $question )
            {
                $name = $question['name'];

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

                if ( in_array($name, self::$basicNames) )
                {
                    if ( $name == 'sex' )
                    {
                        $v = array_values($values);
                        $viewBasic[$name] = array_shift($v);
                    }
                    else
                    {
                        $viewBasic[$name] = $values;
                    }
                }
                else
                {
                    $sectionQuestions[] = array(
                        'id' => $question['id'],
                        'name' => $name,
                        'label' => $questionService->getQuestionLang($name),
                        'value' => $values,
                        'section' => $sectName,
                        'presentation' => $name == 'googlemap_location' ? $name : $question['presentation'],
                        'order' => $question["sortOrder"]
                    );
                }
            }

            usort($sectionQuestions, array('SKADATEIOS_ACTRL_User', 'sortByOrder'));

            $viewQuestions[$sectName] = $sectionQuestions;
        }

        $viewBasic['online'] = (bool) BOL_UserService::getInstance()->findOnlineUserById($userId);
        $viewBasic['avatar'] = SKADATEIOS_ABOL_Service::getInstance()->dataForUserAvatar($userId);
        $viewBasic["displayName"] = BOL_UserService::getInstance()->getDisplayName($userId);

        // compatibility
        if ( $userId != OW::getUser()->getId() )
        {
            $viewBasic['compatibility'] = OW::getEventManager()->call("matchmaking.get_compatibility", array(
                "firstUserId" => OW::getUser()->getId(),
                "secondUserId" => $userId
            ));
        }
        else
        {
            $viewBasic['compatibility'] = null;
        }

        // edit questions
        $editQuestionList = $questionService->findEditQuestionsForAccountType($accountType);
        $editNames = array();

        foreach ( $editQuestionList as $editQuestion )
        {
            $editNames[] = $editQuestion['name'];
        }

        $editQuestionList = $this->getUserEditQuestions($userId, $editNames);
        $editOptions = $questionService->findQuestionsValuesByQuestionNameList($editNames);
        $editData = $questionService->getQuestionData(array($userId), $editNames);
        $editData = !empty($editData[$userId]) ? $editData[$userId] : array();

        $editSections = array();

        foreach ( $editQuestionList['questions'] as $sectionName => $section )
        {
            if ( $sectionName == 'location' )
            {
                continue;
            }

            $order = 0;
            foreach ( $sortedSections as $sorted )
            {
                if ( $sorted->name == $sectionName )
                {
                    $order = $sorted->sortOrder;
                }
            }
            $editSections[] = array('order' => $order, 'name' => $sectionName, 'label' => $questionService->getSectionLang($sectionName));
        }

        usort($editSections, array('SKADATEIOS_ACTRL_User', 'sortByOrder'));

        $editQuestions = array();
        $editBasic = array();

        foreach ( $editQuestionList['questions'] as $sectName => $section )
        {
            $sectionQuestions = array();
            foreach ( $section as $question )
            {
                $data = $editQuestionList['data'][$userId];
                $name = $question['name'];

                $values = "";
                if ( !empty($data[$name]) )
                {
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
                }

                $custom = json_decode($question['custom'], true);

                if ( $name == 'match_age' )
                {
                    $birthday = $questionService->findQuestionByName('birthdate');

                    if ( $birthday !== null && mb_strlen(trim($birthday->custom)) > 0)
                    {
                        $custom = json_decode($birthday->custom, true);
                    }
                }

                $q = array(
                    'id' => $question['id'],
                    'name' => $name,
                    'label' => $questionService->getQuestionLang($name),
                    'value' => $values,
                    'rawValue' => !(empty($editData[$name])) ? $editData[$name] : null,
                    'section' => $sectName,
                    'custom' => $custom,
                    'presentation' => $name == 'googlemap_location' ? $name : $question['presentation'],
                    'options' => self::formatOptionsForQuestion($name, $editOptions),
                    'order' => $question['sortOrder']
                );

                if ( in_array($name, self::$basicNames) )
                {
                    $editBasic[] = $q;
                }
                else
                {
                    $sectionQuestions[] = $q;
                }
            }

            usort($sectionQuestions, array('SKADATEIOS_ACTRL_User', 'sortByOrder'));

            $editQuestions[$sectName] = $sectionQuestions;
        }



        $viewQuestionList = array();
        $editQuestionList = array();

        $viewSectionList = array();
        $editSectionList = array();

        foreach ( $viewSections as $section )
        {
            unset($section['index']);
            $viewSectionList[] = $section;

            $viewQuestionList[] = empty($viewQuestions[$section["name"]])
                    ? array()
                    : $viewQuestions[$section["name"]];
        }

        foreach ( $editSections as $section )
        {
            unset($section['index']);
            $editSectionList[] = $section;

            $editQuestionList[] = empty($editQuestions[$section["name"]])
                    ? array()
                    : $editQuestions[$section["name"]];
        }

        $this->assign('viewQuestions', $viewQuestionList);
        $this->assign('editQuestions', $editQuestionList);

        $this->assign('viewSections', $viewSectionList);
        $this->assign('editSections', $editSectionList);

        $this->assign('viewBasic', $viewBasic);
        $this->assign('editBasic', $editBasic);

        $this->assign('isBlocked', BOL_UserService::getInstance()->isBlocked($userId, OW::getUser()->getId()));
        $pm = OW::getPluginManager();

        $isBookmarked = $pm->isPluginActive('bookmarks') && BOOKMARKS_BOL_Service::getInstance()->isMarked(OW::getUser()->getId(), $userId);
        $this->assign('isBookmarked', $isBookmarked);

        $isWinked = $pm->isPluginActive('winks') && WINKS_BOL_Service::getInstance()->isLimited(OW::getUser()->getId(), $userId);
        $this->assign('isWinked', $isWinked);

        $isAdmin = BOL_AuthorizationService::getInstance()->isActionAuthorizedForUser($userId, BOL_AuthorizationService::ADMIN_GROUP_NAME);
        $this->assign('isAdmin', $isAdmin);

        $auth = array(
            'base.view_profile' => $service->getAuthorizationActionStatus('base', 'view_profile'),
            'photo.upload' => $service->getAuthorizationActionStatus('photo', 'upload'),
            'photo.view' => $service->getAuthorizationActionStatus('photo', 'view'),
        );

        $this->assign('auth', $auth);

        // track guests
        if ( $userId != OW::getUser()->getId() )
        {
            $event = new OW_Event('guests.track_visit', array('userId' => $userId, 'guestId' => OW::getUser()->getId()));

            OW::getEventManager()->trigger($event);
        }

        $allowSendMessage = OW::getPluginManager()->isPluginActive('mailbox');

        $this->assign("actions", array(
            "bookmark" => OW::getPluginManager()->isPluginActive('bookmarks'),
            "message" => $allowSendMessage,
            "wink" => OW::getPluginManager()->isPluginActive('winks') && OW::getPluginManager()->isPluginActive('mailbox')
        ));

        $mailboxModes = OW::getEventManager()->call('mailbox.get_active_mode_list');
        $this->assign("mailboxModes", empty($mailboxModes) ? array() : $mailboxModes);
    }

    public function getSearchQuestions()
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        $user = BOL_UserService::getInstance()->findUserById($userId);

        if ( !$user )
        {
            throw new ApiResponseErrorException();
        }

        $questionService = BOL_QuestionService::getInstance();

        $accTypes = $questionService->findAllAccountTypes();

        $basic = array();
        $advanced = array();
        $selectedSex = null;

        foreach ( $accTypes as $type )
        {
            $searchQuestionList = $questionService->findSearchQuestionsForAccountType($type->name);
            $gender = SKADATE_BOL_AccountTypeToGenderService::getInstance()->getGender($type->name);
            if ( !$selectedSex )
            {
                $selectedSex = $gender;
            }

            $searchNames = array();
            foreach ( $searchQuestionList as $searchQuestion )
            {
                $searchNames[] = $searchQuestion['name'];
            }

            $searchOptions = $questionService->findQuestionsValuesByQuestionNameList($searchNames);
            $questionData = $questionService->getQuestionData(array($userId), $searchNames);
            $questionData = isset($questionData[$userId]) ? $questionData[$userId] : array();

            $basicQuestions = array();
            $advancedQuestions = array();

            foreach ( $searchQuestionList as $searchQuestion )
            {
                $name = $searchQuestion['name'];

                if ( in_array($name, self::$searchBasicNames) )
                {
                    $custom = $searchQuestion['custom']
                        ? json_decode($searchQuestion['custom'], true)
                        : array();

                    // add a distance unit
                    if ( $name == 'googlemap_location' &&  self::$isGoogleMapLocationInstalled )
                    {
                        $custom = array_merge($custom, array(
                           'unit' => OW::getConfig()->getValue('googlelocation', 'distance_units')
                        ));
                    }

                    $array = array(
                        'name' => $name,
                        'label' => $questionService->getQuestionLang($name),
                        'options' => self::formatOptionsForQuestion($name, $searchOptions),
                        'custom' => $custom,
                        'presentation' => $name == 'googlemap_location' ? $name : $searchQuestion['presentation']
                    );

                    if ( in_array($name, self::$searchFilledNames) && isset($questionData[$name]) )
                    {
                        if ( $name == "birthdate" && !empty($array['custom']['year_range']) )
                        {
                            $min = date("Y") - $array['custom']['year_range']['to'];
                            $array['value'] = $min . "-" . ( $min + 15 );
                        }
                        else
                        {
                            $array['value'] = $questionData[$name];
                        }
                    }

                    $basicQuestions[] = $array;
                }
                else
                {
                    $added = false;
                    if ( $advancedQuestions )
                    {
                        foreach( $advancedQuestions as $index => $addedSection )
                        {
                            if ( $addedSection['name'] == $searchQuestion['sectionName'] )
                            {
                                $advancedQuestions[$index]['questions'][] = array(
                                    'name' => $name,
                                    'label' => $questionService->getQuestionLang($name),
                                    'custom' => json_decode($searchQuestion['custom'], true),
                                    'options' => self::formatOptionsForQuestion($name, $searchOptions),
                                    'presentation' => $searchQuestion['presentation']
                                );
                                $added = true;

                                break;
                            }
                        }
                    }

                    if ( !$added )
                    {
                        $section = array();
                        $section['name'] = $searchQuestion['sectionName'];
                        $section['label'] = $questionService->getSectionLang($searchQuestion['sectionName']);
                        $section['questions'][] = array(
                            'name' => $name,
                            'label' => $questionService->getQuestionLang($name),
                            'custom' => json_decode($searchQuestion['custom'], true),
                            'options' => self::formatOptionsForQuestion($name, $searchOptions),
                            'presentation' => $searchQuestion['presentation']
                        );

                        $advancedQuestions[] = $section;
                    }
                }
            }

            $basic[$gender] = $basicQuestions;
            $advanced[$gender] = $advancedQuestions;
        }

        $this->assign('basicQuestions', $basic);
        $this->assign('advancedQuestions', $advanced);

        $data = $questionService->getQuestionData(array($userId), array('match_sex'));

        if ( !empty($data[$userId]['match_sex']) )
        {
            $matchSexValues = $questionService->prepareFieldValue(BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX, $data[$userId]['match_sex']);
            if ( is_array($matchSexValues) && count($matchSexValues) )
            {
                $selectedSex = reset($matchSexValues);
            }
        }

        $this->assign('selectedSex', $selectedSex);

        $service = SKADATEIOS_ABOL_Service::getInstance();
        $auth = array(
            'base.search_users' => $service->getAuthorizationActionStatus('base', 'search_users')
        );
        $this->assign('auth', $auth);
    }

    public function saveQuestion( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        $user = BOL_UserService::getInstance()->findUserById($userId);

        if ( !$user )
        {
            throw new ApiResponseErrorException();
        }

        if ( !isset($params['name']) )
        {
            throw new ApiResponseErrorException();
        }

        $name = trim($params['name']);

        $service = BOL_QuestionService::getInstance();
        $question = $service->findQuestionByName($name);

        if ( !$question )
        {
            throw new ApiResponseErrorException();
        }

        $this->assign('params', $params);

        $prevChangedValues = array();
        $prefValue = BOL_PreferenceService::getInstance()->getPreferenceValue(self::PREFERENCE_LIST_OF_CHANGES, $userId);
        if ( !empty($prefValue) )
        {
            $prevChangedValues = json_decode($prefValue, true);
        }
        
        $changesList = $service->getChangedQuestionList(array($name => $params['rawValue']), $userId);
        $allChangesList = array_merge($prevChangedValues, $changesList);
                
        OW::getEventManager()->trigger(new OW_Event(OW_EventManager::ON_USER_EDIT, array(
            'userId' => $userId,
            'method' => 'native',
            'moderate' => $service->isNeedToModerate($allChangesList)
        )));
        
        BOL_PreferenceService::getInstance()->savePreferenceValue(
                self::PREFERENCE_LIST_OF_CHANGES, json_encode($allChangesList), $userId);
        
        $saved = $service->saveQuestionsData(array($name => $params['rawValue']), $userId);
        $this->assign('dataSaved', $saved);
    }

    public function avatarChange()
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException("Undefined userId");
        }

        if ( empty($_FILES['images']['tmp_name'][0]) )
        {
            throw new ApiResponseErrorException("File was not uploaded");
        }

        $file = $_FILES['images']['tmp_name'][0];

        $userId = OW::getUser()->getId();
        $service = BOL_AvatarService::getInstance();
        $avatar = $service->findByUserId($userId);

        OW::getEventManager()->trigger(new OW_Event('base.before_avatar_change',
            array(
                'userId' => $userId,
                'avatarId' => $avatar ? $avatar->id : null,
                'upload' => false,
                'crop' => true
            )));

        $service->deleteUserAvatar($userId);
        $service->clearCahche($userId);
        $avatarSet = $service->setUserAvatar($userId, $file);

        if ( $avatarSet )
        {
            $avatar = $service->findByUserId($userId, false);

            OW::getEventManager()->trigger(new OW_Event('base.after_avatar_change', array(
                'userId' => $userId,
                'avatarId' => $avatar ? $avatar->id : null,
                'upload' => false,
                'crop' => true
            )));
        }

        $this->assign('avatar', SKADATEIOS_ABOL_Service::getInstance()->dataForUserAvatar($userId));
    }

    public function avatarFromPhoto( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['photoId']) )
        {
            throw new ApiResponseErrorException();
        }

        $photoId = (int) $params['photoId'];
        $photoService = PHOTO_BOL_PhotoService::getInstance();

        $photo = $photoService->findPhotoById($photoId);
        if ( !$photo )
        {
            throw new ApiResponseErrorException("Photo not found");
        }

        $ownerId = $photoService->findPhotoOwner($photoId);

        if ( $ownerId != $userId )
        {
            throw new ApiResponseErrorException("Not authorized");
        }

        $avatarService = BOL_AvatarService::getInstance();
        $tmpPath = $avatarService->getAvatarPluginFilesPath($userId, 3);
        $storage = OW::getStorage();

        $photoPath = $photoService->getPhotoPath($photoId, $photo->hash, 'main');

        $storage->copyFileToLocalFS($photoPath, $tmpPath);

        BOL_AvatarService::getInstance()->setUserAvatar($userId, $tmpPath);
        @unlink($tmpPath);

        $this->assign('avatar', SKADATEIOS_ABOL_Service::getInstance()->dataForUserAvatar($userId));
    }

    public function blockUser( $params )
    {
        $viewerId = OW::getUser()->getId();

        if ( !$viewerId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['userId']) )
        {
            throw new ApiResponseErrorException();
        }

        $userId = (int) $params['userId'];

        $userService = BOL_UserService::getInstance();

        if ( $params["block"] )
        {

            $userService->block($userId);
        }
        else
        {
            $userService->unblock($userId);
        }
    }

    public function setLocation( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['lat']) || empty($params['lon']) )
        {
            throw new ApiResponseErrorException();
        }

        $set = SKADATEIOS_ABOL_Service::getInstance()->setUserCurrentLocation($userId, $params['lat'], $params['lon']);

        $this->assign('status', $set);
    }

    public function sendReport( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['entityId']) || empty($params['entityType']) || !isset($params['reason']) )
        {
            throw new ApiResponseErrorException();
        }

        $entityId = $params['entityId'];
        $entityType = $params['entityType'];

        $userService = BOL_UserService::getInstance();
        $lang = OW::getLanguage();

        $reasons = array(0 => 'spam', 1 => 'offensive', 2 => 'illegal');
        $reason = $lang->text('skadateios', $reasons[$params['reason']]);

        $user = $userService->findUserById($userId);

        $assigns = array(
            'reason' => $reason,
            'reportedUserUrl' => OW_URL_HOME . 'user/' . $user->getUsername()
        );

        switch ( $entityType )
        {
            case 'photo':
                if ( !is_numeric($entityId) )
                {
                    $name = substr($entityId, strrpos($entityId, '/') + 1);
                    $parts = explode("_", $name);
                    $entityId = $parts[1];
                }
                $ownerId = PHOTO_BOL_PhotoService::getInstance()->findPhotoOwner($entityId);
                $reportedUser = $userService->findUserById($ownerId);

                if ( !$reportedUser )
                {
                    throw new ApiResponseErrorException();
                }

                $assigns['userUrl'] = OW_URL_HOME . 'photo/view/'.$entityId.'/latest';

                break;

            case 'avatar':

                $ownerId = $entityId;
                $reportedUser = $userService->findUserById($ownerId);

                if ( !$reportedUser )
                {
                    throw new ApiResponseErrorException();
                }

                $assigns['userUrl'] = OW_URL_HOME . 'user/' . $reportedUser->getUsername();

                break;

            case 'attachment':

                $attachment = MAILBOX_BOL_AttachmentDao::getInstance()->findById($entityId);
                $ext = UTIL_File::getExtension($attachment->fileName);
                $attachmentPath = MAILBOX_BOL_ConversationService::getInstance()->getAttachmentFilePath($attachment->id, $attachment->hash, $ext, $attachment->fileName);

                $assigns['userUrl'] = OW::getStorage()->getFileUrl($attachmentPath);

                break;

            default:
            case 'profile':

                $ownerId = $entityId;
                $reportedUser = $userService->findUserById($ownerId);

                if ( !$reportedUser )
                {
                    throw new ApiResponseErrorException();
                }

                $assigns['userUrl'] = OW_URL_HOME . 'user/' . $reportedUser->getUsername();

                break;
        }

        $subject = $lang->text('skadateios', 'user_reported_subject');
        $text = $lang->text('skadateios', 'user_reported_notification_text', $assigns);
        $html = $lang->text('skadateios', 'user_reported_notification_html', $assigns);

        try
        {
            $email = OW::getConfig()->getValue('base', 'site_email');

            $mail = OW::getMailer()->createMail()
                ->addRecipientEmail($email)
                ->setTextContent($text)
                ->setHtmlContent($html)
                ->setSubject($subject);

            OW::getMailer()->send($mail);
        }
        catch ( Exception $e )
        {
            throw new ApiResponseErrorException();
        }
    }

    ///// Private functions

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

    public static function sortByOrder( $el1, $el2 )
    {
        if ( $el1['order'] === $el2['order'] )
        {
            return 0;
        }

        return $el1['order'] > $el2['order'] ? 1 : -1;
    }

    public function getUserEditQuestions( $userId, $questionNames )
    {
        $questionService = BOL_QuestionService::getInstance();
        $language = OW::getLanguage();

        $questions = $questionService->findQuestionByNameList($questionNames);
        foreach ( $questions as &$q )
        {
            $q = (array) $q;
        }

        $section = null;
        $questionArray = array();
        $questionNameList = array();

        foreach ( $questions as $sort => $question )
        {
            if ( $section !== $question['sectionName'] )
            {
                $section = $question['sectionName'];
            }

            $questions[$sort]['hidden'] = false;

            if ( !$questions[$sort]['onView'] )
            {
                $questions[$sort]['hidden'] = true;
            }

            $questionArray[$section][$sort] = $questions[$sort];
            $questionNameList[] = $questions[$sort]['name'];
        }

        $questionData = $questionService->getQuestionData(array($userId), $questionNameList);
        $questionLabelList = array();

        // add form fields
        foreach ( $questionArray as $sectionKey => $section )
        {
            foreach ( $section as $questionKey => $question )
            {
                $event = new OW_Event('base.questions_field_get_label', array(
                    'presentation' => $question['presentation'],
                    'fieldName' => $question['name'],
                    'configs' => $question['custom'],
                    'type' => 'view'
                ));

                OW::getEventManager()->trigger($event);

                $label = $event->getData();

                $questionLabelList[$question['name']] = !empty($label) ? $label : BOL_QuestionService::getInstance()->getQuestionLang($question['name']);

                $event = new OW_Event('base.questions_field_get_value', array(
                    'presentation' => $question['presentation'],
                    'fieldName' => $question['name'],
                    'value' => empty($questionData[$userId][$question['name']]) ? null : $questionData[$userId][$question['name']],
                    'questionInfo' => $question,
                    'userId' => $userId
                ));

                OW::getEventManager()->trigger($event);

                $eventValue = $event->getData();

                if ( !empty($eventValue) )
                {
                    $questionData[$userId][$question['name']] = $eventValue;

                    continue;
                }

                if ( !empty($questionData[$userId][$question['name']]) )
                {
                    switch ( $question['presentation'] )
                    {
                        case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX:

                            if ( (int) $questionData[$userId][$question['name']] === 1 )
                            {
                                $questionData[$userId][$question['name']] = OW::getLanguage()->text('base', 'yes');
                            }

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_DATE:

                            $format = OW::getConfig()->getValue('base', 'date_field_format');

                            $value = 0;

                            switch ( $question['type'] )
                            {
                                case BOL_QuestionService::QUESTION_VALUE_TYPE_DATETIME:

                                    $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                                    if ( isset($date) )
                                    {
                                        $format = OW::getConfig()->getValue('base', 'date_field_format');
                                        $value = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                                    }

                                    break;

                                case BOL_QuestionService::QUESTION_VALUE_TYPE_SELECT:
                                case BOL_QuestionService::QUESTION_VALUE_TYPE_FSELECT:

                                    $value = (int) $questionData[$userId][$question['name']];

                                    break;
                            }

                            if ( $format === 'dmy' )
                            {
                                $questionData[$userId][$question['name']] = date("d/m/Y", $value);
                            }
                            else
                            {
                                $questionData[$userId][$question['name']] = date("m/d/Y", $value);
                            }

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE:

                            $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                            $questionData[$userId][$question['name']] = UTIL_DateTime::formatBirthdate($date['year'], $date['month'], $date['day']);

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_AGE:

                            $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                            $questionData[$userId][$question['name']] = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']) . " " . $language->text('base', 'questions_age_year_old');

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_RANGE:

                            $range = explode('-', $questionData[$userId][$question['name']]);
                            $questionData[$userId][$question['name']] = $language->text('base', 'form_element_from') . " " . $range[0] . " " . $language->text('base', 'form_element_to') . " " . $range[1];

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_SELECT:
                        case BOL_QuestionService::QUESTION_PRESENTATION_RADIO:
                        case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX:

                            $value = "";
                            $multicheckboxValue = (int) $questionData[$userId][$question['name']];

                            $parentName = $question['name'];

                            if ( !empty($question['parent']) )
                            {
                                $parent = BOL_QuestionService::getInstance()->findQuestionByName($question['parent']);

                                if ( !empty($parent) )
                                {
                                    $parentName = $parent->name;
                                }
                            }

                            $questionValues = BOL_QuestionService::getInstance()->findQuestionValues($parentName);
                            $value = array();

                            foreach ( $questionValues as $val )
                            {
                                /* @var $val BOL_QuestionValue */
                                if ( ( (int) $val->value ) & $multicheckboxValue )
                                {
                                     $value[$val->value] = BOL_QuestionService::getInstance()->getQuestionValueLang($val->questionName, $val->value);
                                }
                            }

                            if ( !empty($value) )
                            {
                                $questionData[$userId][$question['name']] = $value;
                            }

                            break;


                        case BOL_QuestionService::QUESTION_PRESENTATION_FSELECT:

                            $currentValue = (int) $questionData[$userId][$question['name']];

                            $parentName = $question['name'];

                            if ( !empty($question['parent']) )
                            {
                                $parent = BOL_QuestionService::getInstance()->findQuestionByName($question['parent']);

                                if ( !empty($parent) )
                                {
                                    $parentName = $parent->name;
                                }
                            }

                            // get all possible values
                            $questionValues = BOL_QuestionService::getInstance()->findQuestionValues($parentName);

                            $value = array();

                            foreach ( $questionValues as $val )
                            {
                                /* @var $val BOL_QuestionValue */
                                if ( ( (int) $val->value ) == $currentValue )
                                {
                                    $value[$val->value] = BOL_QuestionService::getInstance()->getQuestionValueLang($val->questionName, $val->value);
                                    break;
                                }
                            }

                            if ( !empty($value) )
                            {
                                $questionData[$userId][$question['name']] = $value;
                            }

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_URL:
                        case BOL_QuestionService::QUESTION_PRESENTATION_TEXT:
                        case BOL_QuestionService::QUESTION_PRESENTATION_TEXTAREA:
                            if ( !is_string($questionData[$userId][$question['name']]) )
                            {
                                break;
                            }

                            $value = trim($questionData[$userId][$question['name']]);

                            if ( strlen($value) > 0 )
                            {
                                $questionData[$userId][$question['name']] = UTIL_HtmlTag::autoLink(nl2br($value));
                            }

                            break;

                        default:
                            unset($questionArray[$sectionKey][$questionKey]);
                    }
                }
            }

            if ( isset($questionArray[$sectionKey]) && count($questionArray[$sectionKey]) === 0 )
            {
                unset($questionArray[$sectionKey]);
            }
        }

        return array('questions' => $questionArray, 'data' => $questionData, 'labels' => $questionLabelList);
    }
}