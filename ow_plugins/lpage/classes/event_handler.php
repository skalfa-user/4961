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

class LPAGE_CLASS_EventHandler
{
    use OW_Singleton;

    public function genericInit()
    {
        OW::getEventManager()->bind(OW_EventManager::ON_APPLICATION_INIT, array($this, 'onAppInit'));
    }

    /**
     * Init
     */
    public function init()
    {
        $this->genericInit();

        OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_DEACTIVATE, array($this, 'beforePluginDeactivate'));
    }

    public function beforePluginDeactivate(OW_Event $e)
    {
        $params = $e->getParams();

        if ($params["pluginKey"] == "lpage") {
            OW::getFeedback()->warning(OW::getLanguage()->text("lpage", "deactivate_blocked"));
            throw new RedirectException(OW::getRouter()->urlForRoute("admin_plugins_installed"));
        }
    }

    public function onAppInit()
    {
        $requestHandler = OW::getRequestHandler();

        OW::getDocument()->addStyleDeclaration(".ow_main_menu {display:none !important;}");

        // hide main menu & console for guests
        if (!OW::getUser()->isAdmin()) {
            OW::getDocument()->addStyleDeclaration(".ow_console {display:none !important;}");
        }

        // no catcher logic if it's api context or admin
        if (OW::getApplication()->getContext() == OW_ApiApplication::CONTEXT_API || OW::getUser()->isAdmin()) {
            return;
        }

        $requestHandler->setCatchAllRequestsAttributes("lpage.main", array(OW_RequestHandler::ATTRS_KEY_CTRL => "LPAGE_CTRL_Base", OW_RequestHandler::ATTRS_KEY_ACTION => "index"));

        $excludes = array(
            array("BASE_CTRL_User", "standardSignIn"),
            array("BASE_CTRL_User", "forgotPassword"),
            array("BASE_CTRL_User", "resetPassword"),
            array("BASE_CTRL_User", "resetPasswordCodeExpired"),
            array("BASE_CTRL_User", "resetPasswordRequest"),
            array("CONTACTUS_CTRL_Contact", null),
            array("BASE_CTRL_Captcha", null)
        );

        foreach ($excludes as $exclude) {
            $requestHandler->addCatchAllRequestsExclude("lpage.main", $exclude[0], $exclude[1]);
        }

        // hack to retrieve and use `members only` excludes
        $class = new ReflectionClass("OW_RequestHandler");
        $property = $class->getProperty("catchAllRequestsExcludes");
        $property->setAccessible(true);

        $excludes = $property->getValue($requestHandler);

        if (!empty($excludes["base.members_only"])) {
            foreach ($excludes["base.members_only"] as $item) {
                $rParams = empty($item[OW_RequestHandler::ATTRS_KEY_VARLIST]) ? null : $item[OW_RequestHandler::ATTRS_KEY_VARLIST];
                $requestHandler->addCatchAllRequestsExclude("lpage.main", $item[OW_RequestHandler::ATTRS_KEY_CTRL], $item[OW_RequestHandler::ATTRS_KEY_ACTION], $rParams);
            }
        }
    }

}
