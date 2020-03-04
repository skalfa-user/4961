<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
BOL_BillingService::getInstance()->activateProduct('membership_plan');

OW::getNavigation()->deleteMenuItem('usercredits', 'subscribe_page_heading');
OW::getNavigation()->addMenuItem(OW_Navigation::MAIN, 'membership_subscribe', 'membership', 'subscribe_page_heading', OW_Navigation::VISIBLE_FOR_MEMBER);

OW::getNavigation()->deleteMenuItem('usercredits', 'subscribe_page_heading_mobile');
OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_TOP, 'membership_subscribe', 'membership', 'subscribe_page_heading_mobile', OW_Navigation::VISIBLE_FOR_MEMBER);


$widgetService = BOL_ComponentAdminService::getInstance();

$widget = $widgetService->addWidget('MEMBERSHIP_CMP_MyMembershipWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, BOL_ComponentService::PLACE_DASHBOARD);
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentService::SECTION_LEFT);
$widgetService->saveComponentSettingList($placeWidget->uniqName, array('freeze' => 1));

$widget = $widgetService->addWidget('MEMBERSHIP_CMP_UserMembershipWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, BOL_ComponentService::PLACE_PROFILE);
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentService::SECTION_LEFT);
$widgetService->saveComponentSettingList($placeWidget->uniqName, array('freeze' => 1));

$widget = $widgetService->addWidget('MEMBERSHIP_CMP_PromoWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, BOL_ComponentService::PLACE_INDEX);
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentService::SECTION_SIDEBAR);