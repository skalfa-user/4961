<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */

/**
 * Video IM admin controller
 *
 * @author Alex Ermashev <alexermashev@gmail.com>
 * @package ow_plugin.videoim.controllers
 * @since 1.8.1
 */
class VIDEOIM_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    /**
     * Default action
     */
    public function index()
    {
        $isDemoModeActivated = VIDEOIM_BOL_VideoImService::getInstance()->isDemoModeActivated();

        if ( $isDemoModeActivated )
        {
            // reload the current page
            OW::getFeedback()->error( OW::getLanguage()->text('videoim', 'settings_update_unavailable') );
            $this->redirect( OW::getRouter()->urlForRoute('admin_plugins_installed') );
        }

        // validate and save config
        if ( OW::getRequest()->isPost() && !empty($_POST['url']) )
        {
            $serverList = array();

            // collect server list
            $index = 0;
            foreach ($_POST['url'] as $url)
            {
                // process url
                $url = trim(strip_tags($url));

                if ( $url )
                {
                    $serverList[] = array(
                        'url' => $url,
                        'username' => !empty($_POST['username'][$index])
                            ? trim(strip_tags($_POST['username'][$index]))
                            : null,
                        'credential' => !empty($_POST['credential'][$index])
                            ? trim(strip_tags($_POST['credential'][$index]))
                            : null
                    );
                }

                $index++;
            }

            OW::getConfig()->saveConfig('videoim', 'server_list', json_encode($serverList));

            // reload the current page
            OW::getFeedback()->info(OW::getLanguage()->text('videoim', 'settings_updated'));
            $this->redirect();
        }

        // set current page's settings
        if ( !OW::getRequest()->isAjax() )
        {
            $this->setPageHeading(OW::getLanguage()->text('videoim', 'admin_config'));

            // include necessary js and css files
            OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('videoim')->getStaticCssUrl() . 'admin.css');
        }

        // get default plugin's config values
        $configs = OW::getConfig()->getValues('videoim');
        $serverList = json_decode($configs['server_list']);

        // init view variables
        $this->assign('serverList', $serverList);
    }
}
