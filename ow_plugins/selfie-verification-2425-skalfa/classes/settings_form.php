<?php
/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

/**
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow_plugins.photver.classes
 * @since 1.8.4
 */

class PHOTVER_CLASS_SettingsForm extends Form
{
    protected $pluginKey;
    protected $service;

    public function __construct()
    {
        parent::__construct('settings-form');

        $this->service = PHOTVER_BOL_Service::getInstance();
        $this->pluginKey = $this->service->getPluginKey();

    }

}