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
 * Set credits component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.user_credits.components
 * @since 1.3
 */
class USERCREDITS_CMP_SetCredits extends OW_Component
{
    public function __construct( $userId )
    {
        parent::__construct();

        if ( !OW::getUser()->isAuthorized('usercredits') )
        {
            $this->setVisible(false);
        }
        
        $creditService = USERCREDITS_BOL_CreditsService::getInstance();
        $balance = $creditService->getCreditsBalance($userId);

        $form = new USERCREDITS_CLASS_SetCreditsForm();
        $form->getElement('userId')->setValue($userId);
        $form->getElement('balance')->setValue($balance);

        $this->addForm($form);

        $this->assign('balance', $balance);
    }
}
