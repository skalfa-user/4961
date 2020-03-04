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
 * Credit statistics component
 *
 * @author Alex Ermashev <alexermashev@gmail.com>
 * @package ow.ow_plugins.user_credits.components
 * @since 1.7.6
 */
class USERCREDITS_CMP_CreditStatistic extends OW_Component
{
    /**
     * Default period
     * @var string
     */
    protected $defaultPeriod;

    /**
     * Class constructor
     *
     * @param array $params
     */
    public function __construct( $params )
    {
        parent::__construct();

        $this->defaultPeriod = !empty($params['defaultPeriod'])
            ? $params['defaultPeriod']
            : BOL_SiteStatisticService::PERIOD_TYPE_TODAY;
    }

    /**
     * On before render
     *
     * @return void
     */
    public function onBeforeRender()
    {
        $entityTypes = array(
            USERCREDITS_BOL_CreditsService::SITE_STAT_ENTITY_BUY,
            USERCREDITS_BOL_CreditsService::SITE_STAT_ENTITY_SPENT
        );

        $entityLabels = array(
            USERCREDITS_BOL_CreditsService::SITE_STAT_ENTITY_BUY => OW::getLanguage()->text('usercredits', 'statistics_credit_purchased'),
            USERCREDITS_BOL_CreditsService::SITE_STAT_ENTITY_SPENT => OW::getLanguage()->text('usercredits', 'statistics_credit_spent')
        );

        // register components
        $this->addComponent('statistics',
                new BASE_CMP_SiteStatistic('credit-statistics-chart', $entityTypes, $entityLabels, $this->defaultPeriod));
    }
}

