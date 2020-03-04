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
 * Admin credit statistics widget component
 *
 * @author Alex Ermashev <alexermashev@gmail.com>
 * @package ow.ow_plugins.user_credits.components
 * @since 1.7.6
 */
class USERCREDITS_CMP_CreditStatisticWidget extends ADMIN_CMP_AbstractStatisticWidget
{
    /**
     * Class constructor
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        $this->defaultPeriod = $paramObj->customParamList['defaultPeriod'];
    }

    /**
     * On before render
     *
     * @return void
     */
    public function onBeforeRender()
    {
        // register components
        $this->addComponent('statistics', new USERCREDITS_CMP_CreditStatistic(array(
            'defaultPeriod' => $this->defaultPeriod
        )));

        $this->addMenu('credit');

        // assign view variables
        $this->assign('defaultPeriod', $this->defaultPeriod);
    }

    /**
     * Get standart setting values list
     *
     * @return array
     */
    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_TITLE => OW::getLanguage()->text('usercredits', 'widget_credit_statistics'),
            self::SETTING_ICON => self::ICON_CART,
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true
        );
    }
}
