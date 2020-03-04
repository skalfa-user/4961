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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.controllers
 * @since 1.0
 */
class SKANDROID_ACTRL_Base extends OW_ApiActionController
{
    /**
     * @var SKANDROID_ABOL_Service
     */
    private $service;

    /**
     * @var BOL_UserService
     */
    private $userSerivce;

    /**
     * @return array
     */
    public function getAssignedVars()
    {
        return $this->assignedVars;
    }

    public function __construct()
    {
        $this->service = SKANDROID_ABOL_Service::getInstance();
        $this->userService = BOL_UserService::getInstance();
    }

    public function checkApi()
    {
        $this->assign("data", array("androidApi" => true, "siteUrl" =>  OW_URL_HOME));
    }

    public function siteInfo(array $params = array())
    {
        $config = OW::getConfig();
        $facebookConfig = OW::getEventManager()->call('fbconnect.get_configuration');

        $this->assign("siteInfo", array(
            "siteName" => $config->getValue('base', 'site_name'),
            "facebookAppId" => empty($facebookConfig["appId"]) ? null : trim($facebookConfig["appId"]),
            "facebookPluginActive" => (OW::getPluginManager()->isPluginActive('fbconnect')) ? 'active' : null,
            "userApprove" => (bool)$config->getValue("base", "mandatory_user_approve"),
            "confirmEmail" => (bool)$config->getValue("base", "confirm_email"),
            "maintenance" => (bool)$config->getValue("base", "maintenance"),
            "serverTimezone" => $config->getValue("base", "site_timezone"),
            "serverTimestamp" => time(),
            'analyticsApiKey' => $config->getValue('skandroid', 'analytics_api_key'),
            'billingEnabled' => (bool)$config->getValue('skandroid', 'billing_enabled'),
            'adsApiKey' => $config->getValue('skandroid', 'ads_api_key')
        ));

        $plugins = BOL_PluginService::getInstance()->findActivePlugins();
        $activePluginKeys = array();
        
        /* @var $plugin BOL_Plugin */
        foreach ( $plugins as $plugin )
        {
            $activePluginKeys[] = $plugin->getKey();
        }
        
        $this->assign("activePluginList", $activePluginKeys);
        $this->assign("isUserAuthenticated", OW::getUser()->isAuthenticated());
        
        if ( OW::getUser()->isAuthenticated() )
        {
            $avatarService = BOL_AvatarService::getInstance();
            $userId = OW::getUser()->getId();
            $avatarInfo = $avatarService->findByUserId($userId);

            $questionService = BOL_QuestionService::getInstance();
            $questionData = $questionService->getQuestionData(array($userId), array('birthdate', 'sex'));
            $questionData = $questionData[$userId];
            $date = UTIL_DateTime::parseDate($questionData['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
            $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            $sex = $questionData['sex'];

            $this->assign("userInfo", array(
                "userId" => $userId,
                "displayName" => $this->userService->getDisplayName($userId),
                "avatarUrl" => $avatarService->getAvatarUrl($userId, 1, null, true, false),
                "bigAvatarUrl" => $avatarService->getAvatarUrl($userId, 2, null, true, false),
                "origAvatarUrl" => $avatarService->getAvatarUrl($userId, 3, null, true, false),
                "isAvatarApproved" => !$avatarInfo ? true : $avatarInfo->getStatus() === BOL_ContentService::STATUS_ACTIVE,
                "isSuspended" => $this->userService->isSuspended($userId),
                "isApproved" => $this->userService->isApproved($userId),
                "isEmailVerified" => OW::getUser()->getUserObject()->getEmailVerify(),
                'username' => OW::getUser()->getUserObject()->getUsername(),
                'email' => OW::getUser()->getUserObject()->getEmail(),
                'age' => $age,
                'sex' => $sex,
                'birthday' => array(
                    'year' => $date['year'],
                    'month' => $date['month'],
                    'day' => $date['day']
                )
            ));
            $this->assign("menuInfo", $this->service->getMenu(OW::getUser()->getId()));
        }

        if ( !empty($params['commands']) )
        {
            $event = new OW_Event('skandroid.base.ping', $params);
            OW::getEventManager()->trigger($event);
            $this->assign('ping', $event->getData());
        }
    }

    public function userSiteInfo()
    {
        $this->assign("menu", $this->service->getMenu(OW::getUser()->getId()));
    }
    /*     * *********************************************************** */

    public function uplaoder()
    {
        OW::getLogger()->addEntry("uploader: started");
        OW::getLogger()->addEntry("uploader ( POST ): " . json_encode($_POST));
        OW::getLogger()->addEntry("uploader ( FILES ): " . json_encode($_FILES));
        OW::getLogger()->addEntry("uploader ( REQUEST ): " . json_encode($_REQUEST));
        OW::getLogger()->addEntry("uploader ( SERVER ): " . json_encode($_SERVER));

        $userFilesDir = OW::getPluginManager()->getPlugin("base")->getUserFilesDir();

        $inputFile = fopen("php://input", "r");

        @unlink($userFilesDir . "api-uploaded.jpg");
        $outputFile = fopen($userFilesDir . "api-uploaded.jpg", "a");

        while ( !feof($inputFile) )
        {
            $data = fread($inputFile, 1024);
            fwrite($outputFile, $data, 1024);
        }

        fclose($inputFile);
        fclose($outputFile);

        $this->assign("uploaded", true);
    }

    public function suspended()
    {
        throw new ApiAccessException(ApiAccessException::TYPE_SUSPENDED);
    }

    public function notApproved()
    {
        throw new ApiAccessException(ApiAccessException::TYPE_NOT_APPROVED);
    }

    public function notAuthenticated()
    {
        throw new ApiAccessException(ApiAccessException::TYPE_NOT_AUTHENTICATED);
    }

    public function notVerified()
    {
        throw new ApiAccessException(ApiAccessException::TYPE_NOT_VERIFIED);
    }
    
    public function maintenance()
    {
        throw new ApiAccessException("maintenance");
    }

    public function getText( array $params )
    {
        if ( empty($params['data']) )
        {
            return;
        }

        $result = array();
        $languages = OW::getLanguage();
        $data = json_decode($params['data'], true);

        foreach ( $data as $prefix => $keys )
        {
            foreach ( $keys as $key )
            {
                $result[] = array(
                    'prefix' => $prefix,
                    'key' => $key,
                    'value' => $languages->text($prefix, $key)
                );
            }
        }

        $this->assign('texts', $result);
    }

    public function customPage( array $params )
    {
        if ( empty($params['key']) )
        {
            $this->assign('content', '');

            return;
        }

        $this->assign('content', $this->service->getCustomPage($params['key']));
    }
    
    public function getAutorizationAction( array $params )
    {
        if ( empty($params) || !is_array($params) )
        {
             throw new ApiAccessException("Invalid params");
        }
        $auth = array();
        foreach( $params as $key => $value )
        {
            $auth[$key ."_".$value] = SKANDROID_ABOL_Service::getInstance()->getAuthorizationActionStatus($key, $value);
        }
        
        $this->assign('auth', $auth);
    }
}
