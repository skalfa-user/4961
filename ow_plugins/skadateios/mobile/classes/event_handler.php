<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
class SKADATEIOS_MCLASS_EventHandler
{
    private static $instance;

    public static function getInstance()
    {
        if ( self::$instance === null )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {

    }

    public function init()
    {
        OW::getEventManager()->bind('app.promo_info', array($this, 'getPromoInfo'));
    }

    public function getPromoInfo( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('skadateios' => array(
            'app_url' => OW::getConfig()->getValue('skadateios', 'app_url'),
            'smart_banner_enable' => (bool) OW::getConfig()->getValue('skadateios', 'smart_banner')
        )));
    }
}
