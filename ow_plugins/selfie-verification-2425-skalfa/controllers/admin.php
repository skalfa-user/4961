<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

class PHOTVER_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    /**
     * @var PHOTVER_BOL_Service
     */
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = PHOTVER_BOL_Service::getInstance();
    }

    public function index()
    {
        $config = OW::getConfig();
        $router = OW::getRouter();
        $request = OW::getRequest();
        $feedback = OW::getFeedback();
        $language = OW::getLanguage();
        $application = OW::getApplication();
        $eventManager = OW::getEventManager();
        $pluginManager = OW::getPluginManager();
        $userService = BOL_UserService::getInstance();
        $avatarService = BOL_AvatarService::getInstance();
        $questionService = BOL_QuestionService::getInstance();
        $service = PHOTVER_BOL_Service::getInstance();

        $approvalFormName = 'approval_form';
        $approvalFormUrl = $router->urlForRoute('admin_photver_approval');
        $approvalFormObj = OW::getClassInstance('PHOTVER_CLASS_AdminApprovalForm', $approvalFormName, $approvalFormUrl);
        $approvalFormOButtons = $approvalFormObj->getFormButtons();

        $adminConfirmFloatbox = OW::getClassInstance('PHOTVER_CMP_AdminConfirmFloatbox', $approvalFormObj->getId());
        $adminDeclineFloatbox = OW::getClassInstance('PHOTVER_CMP_AdminDeclineFloatbox', $approvalFormObj->getId());

        $adminConfirmFloatbox->addFloatbox(
            $approvalFormOButtons[0]['id'],
            $approvalFormObj::BUTTON_APPROVE,
            $language->text($service::PLUGIN_KEY, 'approve_confirmation_floatbox'),
            $language->text($service::PLUGIN_KEY, 'approve_confirmation_text_floatbox')
        );
        $adminDeclineFloatbox->addFloatbox(
            $approvalFormOButtons[1]['id'],
            $approvalFormObj::BUTTON_DECLINE,
            $language->text($service::PLUGIN_KEY, 'decline_confirmation_floatbox'),
            $language->text($service::PLUGIN_KEY, 'decline_confirmation_text_floatbox')
        );

        $this->addComponent('adminConfirmFloatbox', $adminConfirmFloatbox);
        $this->addComponent('adminDeclineFloatbox', $adminDeclineFloatbox);

        if( $request->isPost() && !empty($_POST[$approvalFormObj::USER_IDS]) )
        {
            if( isset($_POST[$approvalFormObj::BUTTON_APPROVE]) )
            {
                $approveResult = $approvalFormObj->processApprove();

                if( $approveResult )
                {
                    $feedback->info($language->text($service::PLUGIN_KEY, 'user_approved_feedback'));

                    $this->redirect(OW::getRouter()->urlForRoute('admin_photver_settings'));
                }
            }
            else if( isset($_POST[$approvalFormObj::BUTTON_DECLINE]) )
            {
                $declineResult = $approvalFormObj->processDecline();

                if( $declineResult )
                {
                    $feedback->info($language->text($service::PLUGIN_KEY, 'user_declined_feedback'));

                    $this->redirect(OW::getRouter()->urlForRoute('admin_photver_settings'));
                }
            }
        }

        $this->addForm($approvalFormObj);
        $this->assign('checkboxName', $approvalFormObj::USER_IDS);
        $this->assign('buttons', $approvalFormOButtons);

        $onPage = (int) $config->getValue('base', 'users_on_page');
        $page = isset($_GET['page']) && (int) $_GET['page'] ? (int) $_GET['page'] : 1;
        $first = ($page - 1) * $onPage;

        $userList = $service->findUserListToApprove($first, $onPage);
        $userCount = $service->findUserCountToApprove();

        if( !$userList && $page > 1 )
        {
            $application->redirect($request->buildUrlQueryString(null, array('page' => $page - 1)));
        }

        if( $userList )
        {
            $pages = (int) ceil($userCount / $onPage);
            $paging = new BASE_CMP_Paging($page, $pages, $onPage);
            $userFilesUrl = $pluginManager->getPlugin($service::PLUGIN_KEY)->getUserFilesUrl();

            $userIdList = array();

            foreach( $userList as $index => $user )
            {

                $language->text('base', 'yes');


                $userList[$index]->photoHash = $userFilesUrl . $userList[$index]->photoHash;

                if( !in_array($user->id, $userIdList) )
                {
                    array_push($userIdList, $user->id);
                }
            }

            $avatars = $avatarService->getDataForUserAvatars($userIdList);
            $userNameList = $userService->getUserNamesForList($userIdList);
            $onlineStatus = $userService->findOnlineStatusForUserList($userIdList);
            $questionList = $questionService->getQuestionData($userIdList, array('sex', 'birthdate', 'email'));

            $sexList = array();

            foreach( $userIdList as $id )
            {
                if( empty($questionList[$id]['sex']) )
                {

                    continue;
                }

                $sex = $questionList[$id]['sex'];

                if( !empty($sex) )
                {
                    $sexValue = '';

                    for( $i = 0 ; $i < 31; $i++ )
                    {
                        $val = pow( 2, $i );
                        if( (int)$sex & $val  )
                        {
                            $sexValue .= $questionService->getQuestionValueLang('sex', $val) . ', ';
                        }
                    }

                    if( !empty($sexValue) )
                    {
                        $sexValue = substr($sexValue, 0, -2);
                    }
                }

                $sexList[$id] = $sexValue;
            }

            $this->addComponent('paging', $paging);

            $this->assign('users', $userList);
            $this->assign('total', $userCount);
            $this->assign('sexList', $sexList);
            $this->assign('avatars', $avatars);
            $this->assign('userNameList', $userNameList);
            $this->assign('questionList', $questionList);
            $this->assign('onlineStatus', $onlineStatus);
            $this->assign('pluginFilesPath', $pluginManager->getPlugin($service::PLUGIN_KEY)->getUserFilesUrl());
        }
        else
        {
            $this->assign('users', null);
        }

        $script = '$("#check-all").click(function(){ $("#' . $approvalFormObj->getId() .' input:not(:disabled)[type=checkbox]").attr("checked", $(this).attr("checked") == "checked"); });';

        $this->setPageHeading($language->text($service::PLUGIN_KEY, 'for_approve_title'));
        OW::getDocument()->addOnloadScript($script);
    }

}
