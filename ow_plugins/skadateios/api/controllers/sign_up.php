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
class SKADATEIOS_ACTRL_SignUp extends OW_ApiActionController
{
    public function questionList( $params )
    {
        $questionService = BOL_QuestionService::getInstance();

        $fixedQuestionNames = array("sex", "match_sex", "realname", "email", "password", "username");
        
        if ($params["step"] == 1)
        {
            $questionNames = array("sex", "match_sex");
        }
        else
        {
            $gender = (int) $params["gender"];
            $accountType = SKADATE_BOL_AccountTypeToGenderService::getInstance()->getAccountType($gender);
            $signUpQuestions = $questionService->findSignUpQuestionsForAccountType($accountType);

            foreach ( $signUpQuestions as $question )
            {
                if ( $question["required"] && !in_array($question["name"], $fixedQuestionNames) )
                {
                    $questionNames[] = $question["name"];
                }
            }
        }
        
        $this->assign("list", $this->prepareQuestionList($questionNames));
    }

    public function joinQuestionList( $params )
    {
        $questionService = BOL_QuestionService::getInstance();
        $fixedQuestionNames = array("sex", "match_sex", "password", "email", "username");

        $this->assign("params", array(
            "tos" => OW::getConfig()->getValue("base", "join_display_terms_of_use") ? 1 : 0,
            "avatarRequired" => OW::getConfig()->getValue("base", "join_display_photo_upload") == "display_and_required" ? 1 : 0
        ));

        if ( $params["step"] == 1 )
        {
            $questionNames = array("sex", "match_sex");

            $this->assign("list", array(
                array(
                    "items" => $this->prepareQuestionList($questionNames)
                )
            ));

            return;
        }

        $gender = (int) $params["gender"];
        $accountType = SKADATE_BOL_AccountTypeToGenderService::getInstance()->getAccountType($gender);
        $signUpQuestions = $questionService->findSignUpQuestionsForAccountType($accountType);

        $sections = $questionService->findSortedSectionList();

        $allSections = array();

        /* @var $section BOL_QuestionSection */
        foreach ( $sections as $section )
        {
            $allSections[$section->name] = [
                "name" => $section->name,
                "order" => (int) $section->sortOrder,
                "title" => BOL_QuestionService::getInstance()->getSectionLang($section->name),
                "items" => []
            ];
        }

        $viewSections = [];
        foreach ( $signUpQuestions as $question )
        {
            if ( in_array($question["name"], $fixedQuestionNames) )
            {
                continue;
            }

            $viewSections[$question["sectionName"]] = empty($viewSections[$question["sectionName"]])
                ? $allSections[$question["sectionName"]]
                : $viewSections[$question["sectionName"]];

            $viewSections[$question["sectionName"]]["items"][] = $question["name"];
        }

        $viewSections = array_map(function( array $sectionInfo ) {
            $sectionInfo["items"] = $this->prepareQuestionList($sectionInfo["items"]);

            return $sectionInfo;
        }, $viewSections);

        usort($viewSections, function( $a, $b ) {
            return $a["order"] - $b["order"];
        });

        $this->assign("list", $viewSections);
    }

    protected function prepareQuestionList( $questionNames )
    {
        $questionService = BOL_QuestionService::getInstance();

        $questionList = $questionService->findQuestionByNameList($questionNames);
        $questionOptions = $questionService->findQuestionsValuesByQuestionNameList($questionNames);

        $questions = array();

        usort($questionList, function( BOL_Question $a, BOL_Question $b ) {
            return $a->sortOrder - $b->sortOrder;
        });

        foreach ( $questionList as $question )
        {
            /* @var $question BOL_Question */

            $custom = json_decode($question->custom, true);
            $value = null;

            switch ($question->presentation)
            {
                case BOL_QuestionService::QUESTION_PRESENTATION_RANGE :
                    $value = "18-33";
                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE :
                case BOL_QuestionService::QUESTION_PRESENTATION_AGE :
                case BOL_QuestionService::QUESTION_PRESENTATION_DATE :

                    $value = date("Y-m-d H:i:s", strtotime("-18 year"));
                    break;
            }

            $questions[] = array(
                'id' => $question->id,
                'name' => $question->name,
                'label' => $questionService->getQuestionLang($question->name),
                'custom' => $custom,
                'presentation' => $question->name == 'googlemap_location' ? $question->name : $question->presentation,
                'options' => self::formatOptionsForQuestion($question->name, $questionOptions),
                'required' => (bool) $question->required,

                'value' => $value,
                'rawValue' => $value
            );
        }

        return $questions;
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
    
    public function tryLogIn( $params )
    {
        $fbId = $params["facebookId"];
        $email = $params["email"];
        
        $userId = null;
        
        $authAdapter = new OW_RemoteAuthAdapter($fbId, "facebook");
        
        if ( $authAdapter->isRegistered() )
        {
            $authResult = OW_Auth::getInstance()->authenticate($authAdapter);
            $userId = $authResult->isValid()
                    ? $authResult->getUserId()
                    : null;
        } 
        else
        {
            $userByEmail = BOL_UserService::getInstance()->findByEmail($email);
        
            if ( $userByEmail !== null )
            {
                OW::getUser()->login($userByEmail->id);
                $userId = $userByEmail->id;
            }
        }
        
        $this->assign("loggedIn", !empty($userId));
        
        if ( !empty($userId) )
        {
            $this->respondUserData($userId);
        }
    }
    
    public function save( $params )
    {
        $data = $params["data"];
        
        $authAdapter = new OW_RemoteAuthAdapter($data["facebookId"], "facebook");
        
        $nonQuestions = array("name", "email", "avatarUrl");
        $nonQuestionsValue = array();
        foreach ( $nonQuestions as $name )
        {
            $nonQuestionsValue[$name] = empty($data[$name]) ? null : $data[$name];
            unset($data[$name]);
        }
        
        $data["realname"] = $nonQuestionsValue["name"];
        
        $email = $nonQuestionsValue["email"];
        $password = uniqid();
        
        $user = BOL_UserService::getInstance()->findByEmail($email);
        $newUser = false;
        
        if ( $user === null )
        {
            $newUser = true;
            $username = $this->makeUseranme($nonQuestionsValue["name"]);
            $user = BOL_UserService::getInstance()->createUser($username, $password, $email, null, true);
        }
        
        BOL_QuestionService::getInstance()->saveQuestionsData(array_filter($data), $user->id);
        
        if ( !empty($nonQuestionsValue["avatarUrl"]) )
        {
            $avatarUrl = $nonQuestionsValue["avatarUrl"];
            $pluginfilesDir = OW::getPluginManager()->getPlugin("skadateios")->getPluginFilesDir();
            $ext = UTIL_File::getExtension($avatarUrl);
            $tmpFile = $pluginfilesDir . uniqid("avatar-") . (empty($ext) ? "" : "." . $ext);
            copy($avatarUrl, $tmpFile);
            
            BOL_AvatarService::getInstance()->setUserAvatar($user->id, $tmpFile);
            @unlink($tmpFile);
        }
        
        if ( !$authAdapter->isRegistered() ) 
        {
            $authAdapter->register($user->id);
        }
        
        if ( $newUser )
        {
            $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array(
                'method' => 'facebook',
                'userId' => $user->id,
                'params' => array()
            ));
            OW::getEventManager()->trigger($event);
        }
        
        OW::getUser()->login($user->id);
        $this->respondUserData($user->id);
    }

    public function join( $params )
    {
        $data = $params["data"];

        $event = new OW_Event(OW_EventManager::ON_BEFORE_USER_REGISTER, $data);
        OW::getEventManager()->trigger($event);

        $nonQuestions = array("username", "email", "password", "repeatPassword", "avatar", "avatarKey", "avatarUrl");
        $nonQuestionsValue = array();
        foreach ( $nonQuestions as $name )
        {
            $nonQuestionsValue[$name] = empty($data[$name]) ? null : $data[$name];
            unset($data[$name]);
        }

        $accountType = SKADATE_BOL_AccountTypeToGenderService::getInstance()->getAccountType($data["sex"]);
        $user = BOL_UserService::getInstance()->createUser($nonQuestionsValue['username'], $nonQuestionsValue['password'], $nonQuestionsValue['email'], $accountType);

        // save user data
        if ( !empty($user->id) )
        {
            if ( BOL_QuestionService::getInstance()->saveQuestionsData($data, $user->id) )
            {
                // authenticate user
                OW::getUser()->login($user->id);

                // create Avatar
                BOL_AvatarService::getInstance()->createAvatar($user->id, false, false);

                $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array('userId' => $user->id, 'method' => 'native', 'params' => $params));
                OW::getEventManager()->trigger($event);

                if ( OW::getConfig()->getValue('base', 'confirm_email') )
                {
                    BOL_EmailVerifyService::getInstance()->sendUserVerificationMail($user);
                }
            }
        }

        $this->respondUserData($user->id);
    }
    
    private function respondUserData( $userId )
    {
        $avatarService = BOL_AvatarService::getInstance();
        $userService = BOL_UserService::getInstance();

        $userDto = $userService->findUserById($userId);

        if ( $userDto === null )
        {
            throw new InvalidArgumentException("User not found");
        }

        $this->assign("userId", (int) $userId);
        $this->assign("displayName", $userService->getDisplayName($userId));
        $this->assign("avatar", SKADATEIOS_ABOL_Service::getInstance()->dataForUserAvatar($userId));

        $this->assign("email", $userDto->email);
        $this->assign("suspended", BOL_UserService::getInstance()->isSuspended($userId));
        $this->assign("approved", BOL_UserService::getInstance()->isApproved($userId));
        
        $service = SKADATEIOS_ABOL_Service::getInstance();
        $mainMenu = $service->getMenu($userId, 'main');
        $this->assign("mainMenu", $mainMenu);

        $bottomMenu = $service->getMenu($userId, 'bottom');
        $this->assign("bottomMenu", $bottomMenu);

        $this->assign('newCounter', $service->getNewItemsCount($mainMenu));
        
        $token = OW_Auth::getInstance()->getAuthenticator()->getId();
        $this->assign("token", $token);
    }
    
    private function makeUseranme( $name, $counter = 0 )
    {
        list($fn, $ln) = explode(' ', strtolower($name));
        $username = $fn . mb_substr($ln, 0, 1);
        
        if ( $counter > 0 ) {
            $username .= $counter;
        }
        
        if ( BOL_UserService::getInstance()->isExistUserName($username) )
        {
            return $this->makeUseranme($name, $counter + 1);
        }
        
        return $username;
    }


    public function saveAvatar( $params )
    {
        $avatarService = BOL_AvatarService::getInstance();

        $sessionKey = $avatarService->getAvatarChangeSessionKey();

        if ( !empty($sessionKey) )
        {
            $avatarService->deleteUserTempAvatar($sessionKey);
        }

        $avatarService->setAvatarChangeSessionKey();
        $sessionKey = $avatarService->getAvatarChangeSessionKey();

        if ( empty($_FILES['images']['tmp_name'][0]) )
        {
            throw new ApiResponseErrorException("File was not uploaded");
        }

        $file = $_FILES['images']['tmp_name'][0];

        $avatarPath = $avatarService->getTempAvatarPath($sessionKey, 2);
        move_uploaded_file($file, $avatarPath);
        $avatarUrl = $avatarService->getTempAvatarUrl($sessionKey, 2);

        $this->assign("avatarUrl", $avatarUrl . "?t=" . time());
        $this->assign("avatarKey", $sessionKey);
    }

    public function addAvatar($avatar)
    {
        $avatarService = BOL_AvatarService::getInstance();

        try
        {
            $sessionKey = $avatarService->getAvatarChangeSessionKey();

            // generate a new session key
            if ( !$sessionKey )
            {
                $avatarService->setAvatarChangeSessionKey();
                $sessionKey = $avatarService->getAvatarChangeSessionKey();
            }

            // process avatar's data
            $avatarData = base64_decode( substr($avatar, strpos($avatar, ",") + 1) );
            $path = BOL_AvatarService::getInstance()->getTempAvatarPath($sessionKey, self::AVATAR_SIZE);

            file_put_contents($path, $avatarData);
        }
        catch ( Exception $e )
        {
            return false;
        }

        return $avatarService->getAvatarChangeSessionKey();
    }

    public function checkEmail( $params )
    {
        $user = BOL_UserService::getInstance()->findByEmail($params["email"]);

        $this->assign("valid", $user === null);
    }

    public function checkUsername( $params )
    {
        $user = BOL_UserService::getInstance()->findByUsername($params["username"]);

        $this->assign("valid", $user === null);
    }

    public function verifyEmailCode( $params )
    {
        $code = $params["code"];

        $codeRecord = BOL_EmailVerifyService::getInstance()->findByHash($code);
        $result = BOL_EmailVerifyService::getInstance()->verifyEmailCode($code);

        $this->assign("result", $result["isValid"]);

        if ( $result["isValid"] && $codeRecord !== null )
        {
            $this->respondUserData($codeRecord->userId);
        }
    }

    public function resendEmailCode( $params )
    {
        $email = trim($params["email"]);
        $validator = new BASE_CLASS_EmailVerifyValidator();

        $user = OW::getUser()->getUserObject();

        if ( !empty($email) )
        {
            if ( !$validator->isValid($email) )
            {
                $this->assign("result", false);
                $this->assign("error", $validator->getError());

                return;
            }

            $user->email = $email;
        }

        BOL_UserService::getInstance()->saveOrUpdate($user);
        $this->sendVerificationEmail($user);

        $this->assign("result", true);
    }

    private function sendVerificationEmail($user)
    {
        $vars = array(
            'username' => BOL_UserService::getInstance()->getDisplayName($user->id),
        );

        $params = array(
            'user' => $user,
            'subject' => OW::getLanguage()->text('base', 'site_email_verify_subject'),
            'body_html' => OW::getLanguage()->text('skadateios', 'email_verify_template_html', $vars),
            'body_text' => OW::getLanguage()->text('skadateios', 'email_verify_template_text', $vars),
            'feedback' => false
        );

        BOL_EmailVerifyService::getInstance()->sendVerificationMail(BOL_EmailVerifyService::TYPE_USER_EMAIL, $params);
    }
}