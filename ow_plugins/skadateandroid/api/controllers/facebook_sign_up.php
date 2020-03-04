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
 * @package ow_system_plugins.skadateandroid.api.controllers
 * @since 1.0
 */
class SKANDROID_ACTRL_FacebookSignUp extends OW_ApiActionController
{
    public function getFacebookLoginQuestion( $params )
    {
        $questionService = BOL_QuestionService::getInstance();

        $step = !empty($params['step']) ? $params['step'] : 1;
        //$step = 2; $params['sex'] = 2;
        if ($step == 1) {
            $questionNames = array('sex', 'match_sex');
        }
        elseif ( $step == 2 && !empty($params['sex']))
        {
            $gender = (int) $params['sex'];
            $accountType = SKADATE_BOL_AccountTypeToGenderService::getInstance()->getAccountType($gender);
            $questionNames = array();
            $exclideQuestions = array('sex', 'match_sex', 'password', 'username', 'realname');

            if ( !empty($params['email']) )
            {
                $exclideQuestions[] = 'email';
            }

            foreach ( $questionService->findSignUpQuestionsForAccountType($accountType) as $question )
            {
                if ( in_array($question['name'] , $exclideQuestions) )
                {
                    continue;
                }

                $questionNames[] = $question['name'];
            }
        }

        $questionList = $questionService->findQuestionByNameList($questionNames);
        $sectionNameList = array();

        foreach ( $questionList as $question )
        {
            if ( !in_array($question->sectionName, $sectionNameList) )
            {
                $sectionNameList[] = $question->sectionName;
            }
        }

        $sectionList = $questionService->findSectionBySectionNameList($sectionNameList);

        usort($questionList, function( $a, $b ) use ($sectionList)
        {
            $sectionNameA = $a->sectionName;
            $sectionNameB = $b->sectionName;

            if ( $sectionNameA === $sectionNameB )
            {
                return ((int)$a->sortOrder < (int)$b->sortOrder) ? -1 : 1;
            }

            if ( !isset($sectionList[$sectionNameA]) || !isset($sectionList[$sectionNameB]) )
            {
                return 1;
            }

            return ((int)$sectionList[$sectionNameA]->sortOrder < (int)$sectionList[$sectionNameB]->sortOrder) ? -1 : 1;
        });

        $questionOptions = $questionService->findQuestionsValuesByQuestionNameList($questionNames);
        $questions = $category = array();

        foreach ( $questionList as $question )
        {
            $custom = json_decode($question->custom, true);
            $value = null;

            switch ($question->presentation)
            {
                case BOL_QuestionService::QUESTION_PRESENTATION_RANGE :
                    $birthday = $questionService->findQuestionByName('birthdate');

                    if ( $birthday !== null && mb_strlen(trim($birthday->custom)) > 0)
                    {
                        $custom = json_decode($birthday->custom, true);
                    }

                    $value = '18-33';
                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE :
                case BOL_QuestionService::QUESTION_PRESENTATION_AGE :
                case BOL_QuestionService::QUESTION_PRESENTATION_DATE :
                    $value = date('Y-m-d H:i:s', strtotime('-18 year'));
                    break;
            }

            if ( !isset($category[$question->sectionName]) )
            {
                $category[$question->sectionName] = array(
                    'category' => $question->sectionName,
                    'label' => $questionService->getSectionLang($question->sectionName),
                    'order' => (int)$sectionList[$question->sectionName]->sortOrder
                );
            }

            $questions[] = array(
                'id' => $question->id,
                'name' => $question->name,
                'label' => $questionService->getQuestionLang($question->name),
                'custom' => $custom,
                'presentation' => $question->name == 'googlemap_location' ? $question->name : $question->presentation,
                'options' => SKANDROID_ABOL_Service::getInstance()->formatOptionsForQuestion($question->name, $questionOptions),
                'value' => $value,
                'rawValue' => $value,
                'sectionName' => $question->sectionName,
                'required' => $question->required
            );
        }

        $this->assign('questions', $questions);

        usort($category, function( $a, $b )
        {
            if ( $a['order'] === $b['order'] )
            {
                return 0;
            }
             return $a['order'] < $b['order'] ? -1 : 1;
        });

        $this->assign('category', array_values($category));
    }

    public function saveFacebookLogin( $params )
    {
        if ( empty($params['data']) )
        {
            throw new ApiResponseErrorException();
        }

        $data = json_decode($params['data'], true);
        $facebookId = (int)$data['facebookId'];
        $authAdapter = new OW_RemoteAuthAdapter($facebookId, 'facebook');
//
//        $nonQuestions = array('name', 'email', 'avatarUrl');
//        $nonQuestionsValue = array();
//        foreach ( $nonQuestions as $name )
//        {
//            $nonQuestionsValue[$name] = empty($data[$name]) ? null : $data[$name];
//            unset($data[$name]);
//        }
//
//        $data['realname'] = $nonQuestionsValue['name'];

        $email = trim($data['email']);
        $password = uniqid();

        $tmpUsername = explode('@', $email);
        $username = $tmpUsername[0];

        $username = trim(preg_replace('/[^\w]/', '', $username));
        $username = $this->makeUsername($username);

        $newUser = false;

        try
        {
            $user = BOL_UserService::getInstance()->createUser($username, $password, $email, null, true);
            $newUser = true;
        }
        catch ( LogicException $ex )
        {
            $this->assign('success', false);
            $this->assign('code', $ex->getCode());

            return;
        }

        if ( !empty($data['custom_location']) )
        {
            $data['googlemap_location'] = json_decode($data['custom_location'], true);
            $data['googlemap_location']['json'] = $data['custom_location'];
            unset($data['custom_location']);
        }

        BOL_QuestionService::getInstance()->saveQuestionsData(array_filter($data), $user->id);

        $avatarUrl = 'http://graph.facebook.com/' . $facebookId . '/picture?type=large&height=400&width=400';
        $pluginfilesDir = OW::getPluginManager()->getPlugin('skandroid')->getPluginFilesDir();
        $ext = 'jpg';
        $tmpFile = $pluginfilesDir . uniqid('avatar-') . (empty($ext) ? '' : '.' . $ext);
        copy($avatarUrl, $tmpFile);

        if ( file_exists($tmpFile) )
        {
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

        $this->respondUserData($user->id);
        $this->assign('success', true);
    }

    public function tryLogIn( $params )
    {
        if ( empty($params['facebookId']) )
        {
            throw new ApiResponseErrorException();
        }

        $authAdapter = new OW_RemoteAuthAdapter($params['facebookId'], 'facebook');

        $authResult = OW_Auth::getInstance()->authenticate($authAdapter);

        if ( $authResult->isValid() )
        {
            $this->respondUserData($authResult->getUserId());

            return;
        }

        $questions = FBCONNECT_BOL_Service::getInstance()->requestQuestionValueList($params['facebookId']);

        if ( !empty($questions["email"]) && !empty($questions["username"]) )
        {
            $userByEmail = BOL_UserService::getInstance()->findByEmail($questions['email']);

            if ( $userByEmail !== null )
            {
                $this->respondUserData($userByEmail->id);

                return;
            }
        }

        $this->assign('loggedIn', false);
    }

    private function respondUserData( $userId )
    {
        OW::getUser()->login($userId);
        $token = OW_Auth::getInstance()->getAuthenticator()->getId();

        $baseCtrl = new SKANDROID_ACTRL_Base();
        $baseCtrl->siteInfo();
        foreach ( $baseCtrl->assignedVars as $key => $val )
        {
            $this->assign($key, $val);
        }

        $this->assign("token", $token);
        $this->assign('loggedIn', true);
    }

    public function questionList( $params )
    {
        $questionService = BOL_QuestionService::getInstance();

        $fixedQuestionNames = array('sex', 'match_sex', 'email', 'password', 'username', 'realname');

        if ($params['step'] == 1) {
            $questionNames = array('sex', 'match_sex');
        }
        else
        {
            $gender = (int) $params['gender'];
            $accountType = SKADATE_BOL_AccountTypeToGenderService::getInstance()->getAccountType($gender);
            $signUpQuestions = $questionService->findSignUpQuestionsForAccountType($accountType);

            foreach ( $signUpQuestions as $question )
            {
                if ( $question['required'] && !in_array($question['name'], $fixedQuestionNames) )
                {
                    $questionNames[] = $question['name'];
                }
            }
        }

        $questionList = $questionService->findQuestionByNameList($questionNames);
        $questionOptions = $questionService->findQuestionsValuesByQuestionNameList($questionNames);

        $questions = array();

        foreach ( $questionList as $question )
        {
            /* @var $question BOL_Question */

            $custom = json_decode($question->custom, true);
            $value = null;

            switch ($question->presentation)
            {
                case BOL_QuestionService::QUESTION_PRESENTATION_RANGE :
                    $value = '18-33';
                    break;

                case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE :
                case BOL_QuestionService::QUESTION_PRESENTATION_AGE :
                case BOL_QuestionService::QUESTION_PRESENTATION_DATE :

                    $value = date('Y-m-d H:i:s', strtotime('-18 year'));
                    break;
            }

            $questions[] = array(
                'id' => $question->id,
                'name' => $question->name,
                'label' => $questionService->getQuestionLang($question->name),
                'custom' => $custom,
                'presentation' => $question->name == 'googlemap_location' ? $question->name : $question->presentation,
                'options' => SKANDROID_ABOL_Service::getInstance()->formatOptionsForQuestion($question->name, $questionOptions),
                'value' => $value,
                'rawValue' => $value
            );
        }

        $this->assign('list', array_reverse($questions));
    }
    
    public function save( $params )
    {
        $data = $params['data'];
        
        $authAdapter = new OW_RemoteAuthAdapter($data['facebookId'], 'facebook');
        
        $nonQuestions = array('name', 'email', 'avatarUrl');
        $nonQuestionsValue = array();
        foreach ( $nonQuestions as $name )
        {
            $nonQuestionsValue[$name] = empty($data[$name]) ? null : $data[$name];
            unset($data[$name]);
        }
        
        $data['realname'] = $nonQuestionsValue['name'];
        
        $email = $nonQuestionsValue['email'];
        $password = uniqid();
        
        $user = BOL_UserService::getInstance()->findByEmail($email);
        $newUser = false;
        
        if ( $user === null )
        {
            $newUser = true;
            $username = $this->makeUsername($nonQuestionsValue['name']);
            $user = BOL_UserService::getInstance()->createUser($username, $password, $email, null, true);
        }
        
        BOL_QuestionService::getInstance()->saveQuestionsData(array_filter($data), $user->id);

        if ( !empty($nonQuestionsValue['avatarUrl']) )
        {
            $avatarUrl = $nonQuestionsValue['avatarUrl'];
            $pluginfilesDir = OW::getPluginManager()->getPlugin('skadateios')->getPluginFilesDir();
            $ext = UTIL_File::getExtension($avatarUrl);
            $tmpFile = $pluginfilesDir . uniqid('avatar-') . (empty($ext) ? '' : '.' . $ext);
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
        $this->assign('success', true);
        $this->respondUserData($user->id);
    }
    
    private function makeUsername( $username )
    {
        $counter = 0;

        while ( BOL_UserService::getInstance()->isExistUserName($username) )
        {
            $counter++;
            $username .= $counter;
        }
        
        return $username;
    }
}