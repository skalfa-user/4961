<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * User credits page controller.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.user_credits.controllers
 * @since 1.6.1
 */
class USERCREDITS_CTRL_Credits extends OW_ActionController
{
    public function history()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $creditService = USERCREDITS_BOL_CreditsService::getInstance();

        $lang = OW::getLanguage();
        $page = !empty($_GET['page']) && (int) $_GET['page'] ? abs((int) $_GET['page']) : 1;
        $limit = 20;

        $this->addComponent('items', new USERCREDITS_CMP_HistoryItems($page, $limit));
        $records = $creditService->countUserLogEntries(OW::getUser()->getId());

        // Paging
        $pages = (int) ceil($records / $limit);
        $paging = new BASE_CMP_Paging($page, $pages, 10);
        $this->assign('paging', $paging->render());

        $this->setPageHeading($lang->text('usercredits', 'credits_history_page_heading'));
        OW::getDocument()->setTitle($lang->text('usercredits', 'credits_history_page_heading'));

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'base', 'dashboard');
    }
}