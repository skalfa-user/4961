<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
class MODERATION_CMP_ConsoleItem extends OW_Component
{
    /**
     *
     * @var BASE_CMP_ConsoleDropdownClick
     */
    protected $consoleItem;
    protected $userId;

    public function __construct( $groups, $label, $key, $cssClass )
    {
        parent::__construct();

        $this->userId = OW::getUser()->getId();
        $this->consoleItem = new BASE_CMP_ConsoleDropdownClick($label, $key);
        
        $this->consoleItem->addClass($cssClass);
        $this->assign("items", $groups);
    }

    public function render()
    {
        $this->consoleItem->setContent(parent::render());

        return $this->consoleItem->render();
    }
}