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
 * Grant credits component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.user_credits.components
 * @since 1.5.2
 */
class USERCREDITS_CMP_GrantCredits extends OW_Component
{
    public function __construct( $userId )
    {
        parent::__construct();

        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->setVisible(false);
        }
        
        $creditService = USERCREDITS_BOL_CreditsService::getInstance();
        $amount = $creditService->getGrantableAmountForUser(OW::getUser()->getId());

        $form = new USERCREDITS_CLASS_GrantCreditsForm();
        $form->getElement('userId')->setValue($userId);
        $form->getElement('amount')->setValue($amount);

        $this->addForm($form);
    }
}
