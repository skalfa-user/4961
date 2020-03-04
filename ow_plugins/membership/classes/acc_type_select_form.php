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
 * Select Account type component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.membership.classes
 * @since 1.6.0
 */
class MEMBERSHIP_CLASS_AccTypeSelectForm extends Form
{
    public function __construct()
    {
        parent::__construct('acc-type-select-form');

        $this->setMethod(Form::METHOD_GET);

        $accountType = new Selectbox('accountType');
        $accountType->addAttribute('id', 'account-type-select');

        $accTypes = BOL_QuestionService::getInstance()->findAllAccountTypesWithLabels();

        $accountType->setOptions($accTypes);
        $accountType->setHasInvitation(false);

        $this->addElement($accountType);

        $script =
        '$("#account-type-select").change( function(){
             $(this).parents("form:eq(0)").submit();
         });
         ';

        OW::getDocument()->addOnloadScript($script);
    }
}