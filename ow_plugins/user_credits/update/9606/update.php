<?php

/**
 * Copyright (c) 2012, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'usercredits');

try
{
    $widgetService = Updater::getWidgetService();
    $widget = $widgetService->addWidget('USERCREDITS_CMP_CreditStatisticWidget', false);
    $widgetPlace = $widgetService->addWidgetToPlace($widget, BOL_ComponentAdminService::PLASE_ADMIN_DASHBOARD);
    $widgetService->addWidgetToPosition($widgetPlace, BOL_ComponentService::SECTION_TOP);
}
catch(Exception $e)
{}