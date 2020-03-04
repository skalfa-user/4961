<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 * 
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 * 
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */



class PHOTVER_CTRL_Join extends BASE_CTRL_Join
{
    protected $pluginKey;

    /**
     * @var PHOTVER_BOL_Service
     */
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = PHOTVER_BOL_Service::getInstance();

        $this->pluginKey = $this->service->getPluginKey();
    }

    public function index( $params )
    {
        $urlParams = $_GET;

        if ( is_array($params) && !empty($params) )
        {
            $urlParams = array_merge($_GET, $params);
        }

        parent::index($params);

        if ( !empty($this->joinForm) )
        {

            $this->joinForm->setAction(OW::getRouter()->urlFor('SKADATE_CTRL_Join', 'joinFormSubmit', $urlParams));
            $this->joinForm->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        }

        $this->setTemplate(OW::getPluginManager()->getPlugin($this->pluginKey)->getCtrlViewDir() . 'join_index.html');
    }

    public function joinFormSubmit( $params )
    {
        parent::joinFormSubmit($params);
        $this->setTemplate(OW::getPluginManager()->getPlugin($this->pluginKey)->getCtrlViewDir() . 'join_index.html');
    }

}
