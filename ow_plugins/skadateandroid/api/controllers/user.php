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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.skandroid.api.controllers
 * @since 1.0
 */
class SKANDROID_ACTRL_User extends OW_ApiActionController
{
    const PREFERENCE_LIST_OF_CHANGES = BASE_CTRL_Edit::PREFERENCE_LIST_OF_CHANGES;
    const CHANGE_LIST_SESSION_KEY = 'skandroid.change_list';

    public function getQuestions( $post, $params )
    {
        if ( empty($params['id']) )
        {
            throw new ApiResponseErrorException();
        }

        $userId = (int) $params['id'];
        $user = BOL_UserService::getInstance()->findUserById($userId);

        if ( !$user )
        {
            throw new ApiResponseErrorException();
        }

        $service = SKANDROID_ABOL_Service::getInstance();
        $questionService = BOL_QuestionService::getInstance();
        $accountType = OW::getUser()->getUserObject()->accountType;

        $viewQuestionList = $questionService->findViewQuestionsForAccountType($accountType);
        /* $viewNames = array();

          foreach ( $viewQuestionList as $viewQuestion )
          {
          $viewNames[] = $viewQuestion['name'];
          } */

        $viewQuestionList = BOL_UserService::getInstance()->getUserViewQuestions($userId, false);
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

        usort($viewSections, array('SKANDROID_ACTRL_User', 'sortSectionsAsc'));

        $viewQuestions = array();
        $viewBasic = array();
        $data = $viewQuestionList['data'][$userId];

        $sectionIndex = 0;
        foreach ( $viewQuestionList['questions'] as $sectName => $section )
        {
            $sectionQstions = array();

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
                    else if ( $name == OW::getConfig()->getValue('base', 'display_name_question') )
                    {
                        $viewBasic['realname'] = $values;
                    }
                    else 
                    {
                        $viewBasic[$name] = $values; 
                    }
                }
                else
                {
                    $sectionQstions[] = array(
                        'id' => $question['id'],
                        'name' => $name,
                        'label' => $questionService->getQuestionLang($name),
                        'value' => $values,
                        'section' => $sectName,
                        'presentation' => $name == 'googlemap_location' ? $name : $question['presentation']
                    );
                }
            }
            $viewQuestions[] = $sectionQstions;
        }

        $viewBasic['online'] = (bool) BOL_UserService::getInstance()->findOnlineUserById($userId);
        $viewBasic['avatar'] = self::dataForUserAvatar($userId);

        // compatibility
        if ( $userId != OW::getUser()->getId() )
        {
            $viewBasic['compatibility'] = OW::getEventManager()->call("matchmaking.get_compatibility",
                array(
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

        //$editQuestionList = $this->getUserEditQuestions($userId, $editNames);
        $editOptions = $questionService->findQuestionsValuesByQuestionNameList($editNames);
        $editData = $questionService->getQuestionData(array($userId), $editNames);
        $editData = !empty($editData[$userId]) ? $editData[$userId] : array();

        $editSections = array();
        $editQuestions = array();
        $i = 0;
        $sectionName = null;

        foreach ( $editQuestionList as $question )
        {
            if ( in_array($question['name'], array('email')) )
            {
                continue;
            }

            if ( $sectionName != $question['sectionName'] )
            {
                if ( $sectionName != null )
                {
                    $i++;
                }

                $sectionName = $question['sectionName'];
                $order = 0;

                foreach ( $sortedSections as $sorted )
                {
                    if ( $sorted->name == $sectionName )
                    {
                        $order = $sorted->sortOrder;
                    }
                }

                $editSections[] = array('order' => $order, 'name' => $sectionName, 'label' => $questionService->getSectionLang($sectionName));
                $editQuestions[] = array();
            }

            $name = $question['name'];

            $custom = json_decode($question['custom'], true);

            $options = SKANDROID_ABOL_Service::getInstance()->formatOptionsForQuestion($name, $editOptions);

            $value = !(empty($editData[$name])) ? $editData[$name] : null;
            if ( $name == 'googlemap_location' && !empty($value) )
            {
                $value = json_encode($value);
            }
            elseif ( $name == 'match_age' )
            {
                $birthday = $questionService->findQuestionByName('birthdate');

                if ( $birthday !== null && mb_strlen(trim($birthday->custom)) > 0 )
                {
                    $custom = json_decode($birthday->custom, true);
                }
            }

            $q = array(
                'id' => $question['id'],
                'name' => $name,
                'label' => $questionService->getQuestionLang($name),
                'value' => $value,
                'section' => $question['sectionName'],
                'custom' => empty($custom) ? null : array_merge(array('custom' => 'custom'), $custom),
                'presentation' => $name == 'googlemap_location' ? $name : $question['presentation'],
                'required' => $question['required'],
                'options' => !empty($options['values']) ? $options['values'] : array()
            );

            $editQuestions[$i][] = $q;
        }
        
        usort($editSections, array('SKANDROID_ACTRL_User', 'sortSectionsAsc'));

        $event = new OW_Event(SKANDROID_ACLASS_EventHandler::USER_LIST_PREPARE_USER_DATA, array('listName' => 'user_get_questions'), $editQuestions);
        OW_EventManager::getInstance()->trigger($event);
        
        $this->assign('viewQuestions', $viewQuestions);
        $this->assign('editQuestions', $event->getData());

        $viewSectionList = array();
        $editSectionList = array();

        foreach ( $viewSections as $section )
        {
            unset($section['index']);
            $viewSectionList[] = $section;
        }

        foreach ( $editSections as $section )
        {
            unset($section['index']);
            $editSectionList[] = $section;
        }

        $adat = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array("googlemap_location"));

        $viewBasic["location"] = empty($adat[$userId]["googlemap_location"]["json"]) ? null : $adat[$userId]["googlemap_location"]["address"];
        $this->assign('viewSections', $viewSectionList);
        $this->assign('editSections', $editSectionList);

        $this->assign('viewBasic', $viewBasic);

        $this->assign('isBlocked', BOL_UserService::getInstance()->isBlocked($userId, OW::getUser()->getId()));
        $pm = OW::getPluginManager();

        $isBookmarked = $pm->isPluginActive('bookmarks') && BOOKMARKS_BOL_Service::getInstance()->isMarked(OW::getUser()->getId(),
                $userId);
        $this->assign('isBookmarked', $isBookmarked);

        $isWinked = $pm->isPluginActive('winks') && WINKS_BOL_Service::getInstance()->isLimited(OW::getUser()->getId(),
                $userId);
        $this->assign('isWinked', $isWinked);

        $isAdmin = BOL_AuthorizationService::getInstance()->isActionAuthorizedForUser($userId,
            BOL_AuthorizationService::ADMIN_GROUP_NAME);
        $this->assign('isAdmin', $isAdmin);

        $auth = array(
            'base.view_profile' => $service->getAuthorizationActionStatus('base', 'view_profile'),
            'photo.upload' => $service->getAuthorizationActionStatus('photo', 'upload'),
            'photo.view' => $service->getAuthorizationActionStatus('photo', 'view'),
        );

        $this->assign('isApproved', BOL_UserService::getInstance()->isApproved($userId) ? 'approved': 'false');
        $this->assign('auth', $auth);

        // track guests
        if ( $userId != OW::getUser()->getId() )
        {
            $event = new OW_Event('guests.track_visit', array('userId' => $userId, 'guestId' => OW::getUser()->getId()));

            OW::getEventManager()->trigger($event);
        }

        //printVar($this->assignedVars); exit;
    }

    public static function sortSectionsAsc( $el1, $el2 )
    {
        if ( $el1['order'] === $el2['order'] )
        {
            return 0;
        }

        return $el1['order'] > $el2['order'] ? 1 : -1;
    }

    public function blockUser( $post )
    {
        $viewerId = OW::getUser()->getId();

        if ( !$viewerId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($post['userId']) )
        {
            throw new ApiResponseErrorException();
        }

        $userId = (int) $post['userId'];

        $userService = BOL_UserService::getInstance();

        if ( (bool) $post["block"] )
        {
            $userService->block($userId);
        }
        else
        {
            $userService->unblock($userId);
        }
    }

    public function signout()
    {
        OW::getUser()->logout();
    }
    /*     * ************************************************************* */
    private static $basicNames = array();
    private static $searchBasicNames = array();
    private static $searchFilledNames = array();

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
        }
        self::$searchBasicNames[] = 'relationship';
        self::$searchBasicNames[] = 'birthdate';
    }

    public function authenticate( $post, $params )
    {
        $token = null;

        if ( !OW::getUser()->isAuthenticated() )
        {
            if ( empty($post["username"]) || empty($post["password"]) )
            {
                throw new ApiResponseErrorException();
            }

            $result = OW::getUser()->authenticate(new BASE_CLASS_StandardAuth($post["username"], $post["password"]));

            if ( !$result->isValid() )
            {
                $messages = $result->getMessages();

                throw new ApiResponseErrorException(array(
                "message" => empty($messages) ? "" : $messages[0]
                ));
            }

            $token = OW_Auth::getInstance()->getAuthenticator()->getId();
        }

        $baseCtrl = new SKANDROID_ACTRL_Base();
        $baseCtrl->siteInfo();
        foreach ( $baseCtrl->assignedVars as $key => $val )
        {
            $this->assign($key, $val);
        }

        $this->assign("token", $token);
    }

    public function getInfo( $params, $pathParams )
    {
        $userId = $pathParams["userId"];

        $avatarService = BOL_AvatarService::getInstance();
        $userService = BOL_UserService::getInstance();

        $user = $userService->findUserById($userId);

        $this->assign("avatar", array(
            "url" => $avatarService->getAvatarUrl($userId)
        ));

        $this->assign("displayName", $userService->getDisplayName($userId));

        if ( !empty($user) ) {
            $this->assign("email", $user->email);
        }
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
                    $array = array(
                        'name' => $name,
                        'label' => $questionService->getQuestionLang($name),
                        'options' => SKANDROID_ABOL_Service::getInstance()->formatOptionsForQuestion($name, $searchOptions),
                        'custom' => json_decode($searchQuestion['custom'], true),
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
                        foreach ( $advancedQuestions as $index => $addedSection )
                        {
                            if ( $addedSection['name'] == $searchQuestion['sectionName'] )
                            {
                                $advancedQuestions[$index]['questions'][] = array(
                                    'name' => $name,
                                    'label' => $questionService->getQuestionLang($name),
                                    'custom' => json_decode($searchQuestion['custom'], true),
                                    'options' => SKANDROID_ABOL_Service::getInstance()->formatOptionsForQuestion($name, $searchOptions),
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
                            'options' => SKANDROID_ABOL_Service::getInstance()->formatOptionsForQuestion($name, $searchOptions),
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

        $data = $questionService->getQuestionData(array($userId), array('match_sex', 'sex'));
        $selectedSex = null;

        if ( !empty($data[$userId]['match_sex']) )
        {
            $matchSexValues = $questionService->prepareFieldValue(BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX,
                $data[$userId]['match_sex']);
            if ( is_array($matchSexValues) && count($matchSexValues) )
            {

                if ( !empty($data[$userId]['sex']) && in_array($data[$userId]['sex'], $matchSexValues) )
                {
                    foreach ( $matchSexValues as $value )
                    {
                        if ( $value != $data[$userId]['sex'] )
                        {
                            $selectedSex = $value;
                            break;
                        }
                    }
                }

                if ( !$selectedSex )
                {
                    $selectedSex = reset($matchSexValues);
                }
            }
        }

        $this->assign('selectedSex', $selectedSex);

        BOL_QuestionService::getInstance()->getQuestionData(array(OW::getUser()->getId()), array('sex', 'match_sex'));

        $service = SKANDROID_ABOL_Service::getInstance();
        $auth = array(
            'base.search_users' => $service->getAuthorizationActionStatus('base', 'search_users')
        );
        $this->assign('auth', $auth);

        if ( OW::getPluginManager()->isPluginActive("googlelocation") ) {
            $this->assign("distanceUnits", GOOGLELOCATION_BOL_LocationService::getInstance()->getDistanseUnits());
        }
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
        $value = $params['value'];
        if ( $name == 'custom_location' )
        {
            $name = 'googlemap_location';
            $value = json_decode($params['value'], true);
            $value['json'] = $params['value'];
        }

        $service = BOL_QuestionService::getInstance();
        $question = $service->findQuestionByName($name);

        if ( !$question )
        {
            throw new ApiResponseErrorException();
        }

        $this->assign('params', $params);

        $changesList = $service->getChangedQuestionList(array($name => $value), $userId);
        $saved = $service->saveQuestionsData(array($name => $value), $userId);

        if ( $saved )
        {
            $this->assign('isNeedToModerate', $service->isNeedToModerate($changesList));

            $session = OW::getSession();
            $sessionChangeList = $session->isKeySet(self::CHANGE_LIST_SESSION_KEY) ? json_decode($session->get(self::CHANGE_LIST_SESSION_KEY),
                    true) : array();
            $sessionChangeList = array_merge($sessionChangeList, $changesList);
            $session->set(self::CHANGE_LIST_SESSION_KEY, json_encode($sessionChangeList));
        }

        $this->assign('dataSaved', $saved);
    }

    public function avatarChange()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new ApiResponseErrorException("Undefined userId");
        }

        if ( empty($_FILES['avatar']['tmp_name']) )
        {
            throw new ApiResponseErrorException("File was not uploaded");
        }

        $userId = OW::getUser()->getId();
        $service = BOL_AvatarService::getInstance();
        $file = $_FILES['avatar']['tmp_name'];
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
        $service->setUserAvatar($userId, $file);

        $avatar = $service->findByUserId($userId, false);

        OW::getEventManager()->trigger(new OW_Event('base.after_avatar_change',
            array(
            'userId' => $userId,
            'avatarId' => $avatar ? $avatar->id : null,
            'upload' => false,
            'crop' => true
        )));

        $this->assign('avatar', self::dataForUserAvatar($userId));
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

        $this->assign('avatar', self::dataForUserAvatar($userId));
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

        $set = SKANDROID_ABOL_Service::getInstance()->setUserCurrentLocation($userId, $params['lat'], $params['lon']);

        $this->assign('status', $set);
    }

    ///// Private functions

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
        $reason = $lang->text('skandroid', $reasons[$params['reason']]);

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

                $assigns['userUrl'] = OW_URL_HOME . 'photo/view/' . $entityId . '/latest';

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
                $attachmentPath = MAILBOX_BOL_ConversationService::getInstance()->getAttachmentFilePath($attachment->id,
                    $attachment->hash, $ext, $attachment->fileName);

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

        $subject = $lang->text('skandroid', 'user_reported_subject');
        $text = $lang->text('skandroid', 'user_reported_notification_text', $assigns);
        $html = $lang->text('skandroid', 'user_reported_notification_html', $assigns);

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

    private static function dataForUserAvatar( $userId )
    {
        $avatar = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId), true, false);
        $result = $avatar[$userId];
        if ( !empty($result['labelColor']) )
        {
            $color = explode(', ', trim($result['labelColor'], 'rgba()'));
            $result['labelColor'] = array('r' => $color[0], 'g' => $color[1], 'b' => $color[2]);
        }
        else
        {
            $result['labelColor'] = array('r' => '100', 'g' => '100', 'b' => '100');
        }

        $bigAvatar = SKADATE_BOL_Service::getInstance()->findAvatarByUserId($userId);
        $result["bigAvatarUrl"] = $bigAvatar ? SKADATE_BOL_Service::getInstance()->getAvatarUrl($userId,
                $bigAvatar->hash) : BOL_AvatarService::getInstance()->getAvatarUrl($userId, 2, null, true, false);

        if ( ($avatar = BOL_AvatarService::getInstance()->findByUserId($userId)) !== null )
        {
            $result["approved"] = $avatar->getStatus() == BOL_ContentService::STATUS_ACTIVE;
        }

        return $result;
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
                $event = new OW_Event('base.questions_field_get_label',
                    array(
                    'presentation' => $question['presentation'],
                    'fieldName' => $question['name'],
                    'configs' => $question['custom'],
                    'type' => 'view'
                ));

                OW::getEventManager()->trigger($event);

                $label = $event->getData();

                $questionLabelList[$question['name']] = !empty($label) ? $label : BOL_QuestionService::getInstance()->getQuestionLang($question['name']);

                $event = new OW_Event('base.questions_field_get_value',
                    array(
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
            }

            if ( isset($questionArray[$sectionKey]) && count($questionArray[$sectionKey]) === 0 )
            {
                unset($questionArray[$sectionKey]);
            }
        }

        return array('questions' => $questionArray, 'data' => $questionData, 'labels' => $questionLabelList);
    }

    public function markApproval( $params )
    {
        if ( !isset($params['userId']) || !OW::getUser()->isAuthenticated() || $params['userId'] != OW::getUser()->getId() )
        {
            throw new ApiAccessException(ApiAccessException::TYPE_NOT_AUTHENTICATED);
        }

        $userId = $params['userId'];

        $session = OW::getSession();
        $sessionChangeList = $session->isKeySet(self::CHANGE_LIST_SESSION_KEY) ? json_decode($session->get(self::CHANGE_LIST_SESSION_KEY),
                true) : array();
        $session->delete(self::CHANGE_LIST_SESSION_KEY);

        OW::getEventManager()->trigger(new OW_Event(OW_EventManager::ON_USER_EDIT,
            array(
            'userId' => $userId,
            'method' => 'native',
            'moderate' => BOL_QuestionService::getInstance()->isNeedToModerate($sessionChangeList)
        )));

        if ( BOL_UserService::getInstance()->isApproved($userId) )
        {
            $sessionChangeList = array();
        }

        BOL_PreferenceService::getInstance()->savePreferenceValue(self::PREFERENCE_LIST_OF_CHANGES,
            json_encode($sessionChangeList), $userId);
    }

    public function addDeviceId( array $params )
    {
        SKANDROID_BOL_PushService::getInstance()->registerDevice($params["token"], OW::getUser()->getId(),
            array(SKANDROID_BOL_PushService::PROPERTY_LANG => $params["lang"]));
    }
}
