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
 * Membership settings form
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.membership.classes
 * @since 1.7.1
 */
class MEMBERSHIP_CLASS_SettingsForm extends Form
{
    public function __construct( )
    {
        parent::__construct('settings-form');

        $lang = OW::getLanguage();

        $period = new TextField('period');
        $period->setLabel($lang->text('membership', 'remind_expiration'));
        $period->setRequired(true);
        $period->addValidator(new IntValidator(2, 10000));
        $this->addElement($period);

        $submit = new Submit('save');
        $submit->setValue($lang->text('base', 'edit_button'));
        $this->addElement($submit);
    }
}