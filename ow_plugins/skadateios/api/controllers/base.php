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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.controllers
 * @since 1.0
 */
class SKADATEIOS_ACTRL_Base extends OW_ApiActionController
{
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
        
        while(!feof($inputFile))
        {
            $data = fread($inputFile, 1024);
            fwrite($outputFile, $data, 1024);
        }

        fclose($inputFile);
        fclose($outputFile);
        
        $this->assign("uploaded", true);
    }
    
    public function siteInfo()
    {
        $config = OW::getConfig();
        
        $this->assign("name", $config->getValue('base', 'site_name'));
        $this->assign("url", OW_URL_HOME);
        
        $plugins = array();
        $pluginDtos = BOL_PluginService::getInstance()->findActivePlugins();
        /* @var $plugin BOL_Plugin */
        foreach ( $pluginDtos as $plugin )
        {
            $plugins[] = $plugin->getKey();
        }
        
        $this->assign("plugins", $plugins);
        
        $facebookConfig = OW::getEventManager()->call('fbconnect.get_configuration');
     
        $this->assign("facebookConnect", array(
            "active" => $facebookConfig === null ? 0 : 1,
            "config" => $facebookConfig
        ));
        
        $fluryApiKey = $config->getValue('skadateios', 'flurry_api_key');
        $this->assign("flury", array(
            "apiKey" => $fluryApiKey,
            "active" => (bool) trim($fluryApiKey)
        ));
        
        $admodApiKey = $config->getValue('skadateios', 'admod_api_key');
        $this->assign("admod", array(
            "apiKey" => $admodApiKey,
            "active" => (bool) trim($admodApiKey)
        ));
    }

    public function customPage( array $params )
    {
        if ( empty($params['key']) )
        {
            $this->assign('content', '');

            return;
        }

        $service = SKADATEIOS_ABOL_Service::getInstance();

        $this->assign('content', $service->getCustomPage($params['key']));
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
        throw new ApiAccessException(ApiAccessException::TYPE_NOT_VERIFIED, array(
            "email" => OW::getUser()->getEmail()
        ));
    }
}