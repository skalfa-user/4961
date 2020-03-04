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
class SKANDROID_ACTRL_SignUp extends OW_ApiActionController
{
    use BASE_CLASS_UploadTmpAvatarTrait;

    const STEP_1 = 1;
    const STEP_2 = 2;

    private function sort(&$list) {
        usort($list, function( $a, $b ) {
            if ( $a['order'] === $b['order'] )
            {
                return 0;
            }

            return $a['order'] > $b['order'] ? 1 : -1;
        });

        return $list;
    }

    private function getSectionList( $questionList )
    {
        if ( empty($questionList) )
        {
            return array();
        }

        $sectionList = array();
        $sectionNameList = array();

        foreach( $questionList as $question ) {
            if ( !empty($question["sectionName"]) ) {
                $sectionNameList[$question["sectionName"]] = $question["sectionName"];
            }
        }

        $sections = BOL_QuestionService::getInstance()->findSectionBySectionNameList($sectionNameList);

        if ( empty($sections) )
        {
            return array();
        }

        foreach ( $sections as $section ) {
            /* @var $section BOL_QuestionSection */
            $sectionList[] = array(
                'order' => $section->sortOrder,
                'name' => $section->name,
                'label' => BOL_QuestionService::getInstance()->getSectionLang($section->name)
            );
        }

        $this->sort($sectionList);

        return $sectionList;
    }

    private function getQuestionList( $questionNames, $sortQuestions = false )
    {
        if ( empty($questionNames) )
        {
            return array();
        }

        $questionService = BOL_QuestionService::getInstance();

        $questionList = $questionService->findQuestionByNameList($questionNames);
        $questionOptions = $questionService->findQuestionsValuesByQuestionNameList($questionNames);

        $questions = array();

        foreach ( $questionNames as $name )
        {
            if( empty($questionList[$name]) )
            {
                continue;
            }

            $question = $questionList[$name];

            /* @var $question BOL_Question */

            $custom = json_decode($question->custom, true);
            $value = null;

            switch ($question->presentation)
            {
                case BOL_QuestionService::QUESTION_PRESENTATION_RANGE :
                    $value = '18-33';

                    if ( !empty($birthday) && !empty($birthday->custom) )
                    {
                        $custom = json_decode($birthday->custom, true);
                    }

                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE :
                case BOL_QuestionService::QUESTION_PRESENTATION_AGE :
                case BOL_QuestionService::QUESTION_PRESENTATION_DATE :

                    $value = date('Y-m-d H:i:s', strtotime('-18 year'));
                    break;
            }

            $values = SKANDROID_ABOL_Service::getInstance()->formatOptionsForQuestion($question->name, $questionOptions);

            $questions[] = array(
                'id' => $question->id,
                'name' => $question->name == 'googlemap_location' ? 'custom_location' : $question->name,
                'label' => $questionService->getQuestionLang($question->name),
                'value' => $value,
                'required' => $question->required,
                'section' => $question->sectionName,
                'custom' => empty($custom) ? null : array_merge(array('custom' => 'custom'), $custom),
                'order' => $question->sortOrder,
                'presentation' => $question->name == 'googlemap_location' ? $question->name : $question->presentation,
                'options' => !empty($values['values'])? $values['values'] : array(),
                'rawValue' => $value
            );
        }

        if ( $sortQuestions ) {
            $this->sort($questions);
        }

		$event = new OW_Event(SKANDROID_ACLASS_EventHandler::USER_LIST_PREPARE_USER_DATA, array('listName' => 'sign_in_get_question_list'), $questions);
        OW_EventManager::getInstance()->trigger($event);

        return $event->getData();
    }

    public function questionList( $params )
    {
        $questionService = BOL_QuestionService::getInstance();
        $signUpQuestions = array();
        $sortQuestions = false;

        $questionNames = array();
        $firstStepQuestions = array('sex', 'match_sex', 'email', 'password', 'username');

        if ($params['step'] == self::STEP_1)
        {
            $questionNames = $firstStepQuestions;
            $questionList = $questionService->findQuestionByNameList($questionNames);

            if ( !empty($questionList) )
            {
                foreach ( $questionNames as $name )
                {
                    foreach ($questionList as $question)
                    {
                        if ( !empty($question) && $question->name == $name )
                        {
                            $signUpQuestions[$question->name] = get_object_vars($question);
                            break;
                        }
                    }
                }
            }
        }
        else if ($params['step'] == self::STEP_2 )
        {
            $gender = (int) !empty($params['sex']) ? $params['sex'] : 0;
            $accountType = SKADATE_BOL_AccountTypeToGenderService::getInstance()->getAccountType($gender);
            $signUpQuestions = $questionService->findSignUpQuestionsForAccountType($accountType);

            foreach ( $signUpQuestions as $key => $question )
            {
                if ( in_array($question['name'], $firstStepQuestions) )
                {
                    unset($signUpQuestions[$key]);
                }
            }

            foreach ( $signUpQuestions as $question )
            {
                $questionNames[$question['name']] = $question['name'];
            }

            $sortQuestions = true;
        }

        $isAvatarRequired = false;
        $isDisplayAvatar = false;

        $displayPhotoUpload = OW::getConfig()->getValue('base', 'join_display_photo_upload');

        switch ( $displayPhotoUpload )
        {
            case BOL_UserService::CONFIG_JOIN_DISPLAY_PHOTO_UPLOAD :
                $isDisplayAvatar = true;
                break;

            case BOL_UserService::CONFIG_JOIN_DISPLAY_AND_SET_REQUIRED_PHOTO_UPLOAD :
                $isDisplayAvatar = true;
                $isAvatarRequired = true;
                break;
        }


        $displayTermsOfUse = false;

        if ( OW::getConfig()->getValue('base', 'join_display_terms_of_use') )
        {
            $displayTermsOfUse = true;
        }

        $this->assign('avatarRequired', $isAvatarRequired);
        $this->assign('displayAvatar', $isDisplayAvatar);

        $this->assign('termsOfUseRequired', $displayTermsOfUse);

        $this->assign('displayTermsOfUser', $this->getQuestionList($questionNames), $sortQuestions);

        $this->assign('questions', $this->getQuestionList($questionNames), $sortQuestions);

        $this->assign('sections', $this->getSectionList($signUpQuestions));
    }

    public function validate( $params )
    {
        if ( empty($params) || !is_array($params) )
        {
            return;
        }

        $validationInfo = $this->validateQuestions($params);

        $this->assign("data", $validationInfo);
    }

    private function validateQuestions( $params )
    {
        if ( empty($params) )
        {
            return array();
        }

        $validationInfo = array();

        $questionList = BOL_QuestionService::getInstance()->findQuestionByNameList(array_keys($params));

        $birthdayConfig = null;
        $birthday = BOL_QuestionService::getInstance()->findQuestionByName("birthdate");
        if ( !empty($birthday) )
        {
            $birthdayConfig = ($birthday->custom);
        }

        foreach ( $params as $key => $value )
        {
            $validator = null;

            switch ($key)
            {
                case "username":
                    $validator = new BASE_CLASS_JoinUsernameValidator();
                    break;

                case "email":
                    $validator = new BASE_CLASS_JoinEmailValidator();
                    break;

                case "verifyEmail":
                    $validator = new BASE_CLASS_EmailVerifyValidator();
                    break;

                case "verifyEmailCode":
                    $validator = new BASE_CLASS_VerificationCodeValidator();
                    break;
            }

            if ( !empty($questionList[$key]) && $validator == null )
            {
                switch ($questionList[$key]->presentation) {

                    case self::QUESTION_PRESENTATION_BIRTHDATE :
                    case self::QUESTION_PRESENTATION_AGE :
                    case self::QUESTION_PRESENTATION_DATE :
                        $class = new DateField("");
                        $validator = new DateValidator($class->getMinYear(), $class->getMaxYear());
                        break;

                    case self::QUESTION_PRESENTATION_RANGE :
                        $class = new Range("");

                        $validator = new RangeValidator();

                        if (!empty($birthdayConfig) && mb_strlen(trim($birthdayConfig)) > 0) {
                            $configsList = json_decode($birthdayConfig, true);
                            foreach ($configsList as $name => $value) {
                                if ($name = 'year_range' && isset($value['from']) && isset($value['to'])) {
                                    $validator->setMinValue(date("Y") - $value['to']);
                                    $validator->setMaxValue(date("Y") - $value['from']);
                                    $class->setMinValue(date("Y") - $value['to']);
                                    $class->setMaxValue(date("Y") - $value['from']);
                                }
                            }
                        }

                        break;
                }

            }

            if ($validator != null) {
                $validation = array(
                    "name" => $key,
                    "isValid" => $validator->isValid($value),
                    "message" => $validator->getError()
                );

                array_push($validationInfo, $validation);
            }
        }

        return $validationInfo;
    }

    public function save( $params )
    {
        if ( empty($params) || !is_array($params) )
        {
            throw new ApiAccessException("Invalid params");
        }

        $joinData = $params;

        if ( !OW::getPluginManager()->isPluginActive("skadate") )
        {
            $this->assign("data",array("result" => "false", "userId" => 0, "message" => ""));
            return;
        }

        if( empty($joinData['username']) || empty($joinData['email']) || empty($joinData['password']) || empty($joinData['sex']) )
        {
            throw new ApiResponseErrorException();
        }

        $sex = $joinData['sex'];

        $accountType = SKADATE_BOL_AccountTypeToGenderService::getInstance()->getAccountType($sex);

        if( empty($accountType) )
        {
            throw new ApiResponseErrorException();
        }

        $event = new OW_Event(OW_EventManager::ON_BEFORE_USER_REGISTER, $joinData);
        OW::getEventManager()->trigger($event);

        $language = OW::getLanguage();
        // create new user
        $user = BOL_UserService::getInstance()->createUser($joinData['username'], $joinData['password'], $joinData['email'], $accountType);

        unset($joinData['username']);
        unset($joinData['password']);
        unset($joinData['email']);
        unset($joinData['accountType']);

        // save user data
        if ( !empty($user->id) )
        {
            if( !empty( $joinData['fileKey'] ) )
            {
                OW::getSession()->set(BOL_AvatarService::AVATAR_CHANGE_SESSION_KEY, $joinData['fileKey']);
            }

            foreach ( $joinData as $key => $value )
            {
                if ( $key == 'custom_location' )
                {
                    $json = $value;
                    $joinData['googlemap_location'] = json_decode($json, true);
                    $joinData['googlemap_location']['json'] = $json;
                    unset($joinData[$key]);
                }
            }

            if ( BOL_QuestionService::getInstance()->saveQuestionsData($joinData, $user->id) )
            {
                // create Avatar
                BOL_AvatarService::getInstance()->createAvatar($user->id, false, false);

                $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array('userId' => $user->id, 'method' => 'native', 'params' => $params));
                OW::getEventManager()->trigger($event);

                if ( OW::getConfig()->getValue('base', 'confirm_email') )
                {
                   $this->sendVerificationEmail($user);
                }

                $this->assign("data", array(
                    "result" => "true",
                    "userId" => $user->id,
                    "message" => OW::getLanguage()->text('base', 'join_successful_join'),
                    "confirmEmail" => OW::getConfig()->getValue('base', 'confirm_email')
                ));
            }
            else
            {
                $this->assign("data", array(
                    "result" => "false",
                    "userId" => $user->id,
                    "message" => $language->text('base', 'join_join_error'),
                ));
            }
        }
        else
        {
            $this->assign("data", array("result" => "false", "userId" => 0, "message" => $language->text('base', 'join_join_error')));
        }
    }

    public function uploadAvatar()
    {
        $result = array("result" => false);
        $avatarService = BOL_AvatarService::getInstance();

        if ( isset($_FILES['avatar']) )
        {
            BOL_AvatarService::getInstance()->setAvatarChangeSessionKey();
            $result = $this->uploadTmpAvatar($_FILES['avatar']);
        }

        $result['fileKey'] = $avatarService->getAvatarChangeSessionKey();

        if ( $result["result"] != false )
        {
            @copy($avatarService->getTempAvatarPath($result['fileKey'], 3), $avatarService->getTempAvatarPath($result['fileKey'], 2));
        }

        $this->assign("result", $result);
    }

    public function verifyEmail($params)
    {
        if ( empty($params) || !is_array($params) || !isset($params["verifyEmailCode"]) )
        {
            throw new ApiAccessException("Invalid params");
        }
        $result = BOL_EmailVerifyService::getInstance()->verifyEmailCode($params["verifyEmailCode"]);
        $this->assign("isValid", !empty($result['isValid']) ? $result['isValid'] : false);
    }

    public function resendVerificationEmail($params)
    {
        if ( empty($params) || !is_array($params) || !isset($params["userId"]) )
        {
            throw new ApiAccessException("Invalid params");
        }

        $user = BOL_UserService::getInstance()->findUserById($params["userId"]);

        if ( empty($user) )
        {
            throw new ApiAccessException("Invalid params");
        }

        if ( !empty($params['email']) )
        {
            $validator = new BASE_CLASS_EmailVerifyValidator();

            if ( $validator->isValid($params['email']) )
            {
                $user->email = $params['email'];
                BOL_UserService::getInstance()->saveOrUpdate($user);
            }
        }

        $this->sendVerificationEmail($user);

        $this->assign("isValid", true);
    }

    private function sendVerificationEmail($user)
    {
        $vars = array(
            'username' => BOL_UserService::getInstance()->getDisplayName($user->id),
        );

        $params = array(
            'user' => $user,
            'subject' => OW::getLanguage()->text('base', 'site_email_verify_subject'),
            'body_html' => OW::getLanguage()->text('skandroid', 'email_verify_template_html', $vars),
            'body_text' => OW::getLanguage()->text('skandroid', 'email_verify_template_text', $vars),
            'feedback' => false
        );

        BOL_EmailVerifyService::getInstance()->sendVerificationMail(BOL_EmailVerifyService::TYPE_USER_EMAIL, $params);
    }
}
