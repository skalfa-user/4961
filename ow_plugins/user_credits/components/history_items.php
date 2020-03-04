<?php

/**
 * Copyright (c) 2012, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * Credits history item list component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.user_credits.components
 * @since 1.6.1
 */
class USERCREDITS_CMP_HistoryItems extends OW_Component
{
    public function __construct( $page, $limit )
    {
        parent::__construct();

        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->setVisible(false);
        }

        $userId = OW::getUser()->getId();
        $creditService = USERCREDITS_BOL_CreditsService::getInstance();

        $history = $creditService->getUserLogHistory($userId, $page, $limit);
        $this->assign('history', $history);
    }
}