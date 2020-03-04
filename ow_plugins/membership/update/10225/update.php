<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
Updater::getLanguageService()->deleteLangKey('membership', 'plan_struct');
Updater::getLanguageService()->deleteLangKey('membership', 'membership_and_credits');
Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'membership');

$navigationService = Updater::getNavigationService();

$navigationService->deleteMenuItem('usercredits', 'subscribe_page_heading');
$logger = Updater::getLogger();

try
{
    $navigationService->addMenuItem(OW_Navigation::MAIN, 'membership_subscribe', 'membership', 'subscribe_page_heading', OW_Navigation::VISIBLE_FOR_MEMBER);
}

catch (Exception $e)
{
    $logger->addEntry($e->getMessage());
}

$navigationService->deleteMenuItem('usercredits', 'subscribe_page_heading_mobile');

try
{
    $navigationService->addMenuItem(OW_Navigation::MOBILE_TOP, 'membership_subscribe', 'membership', 'subscribe_page_heading_mobile', OW_Navigation::VISIBLE_FOR_MEMBER);
}

catch (Exception $e)
{
    $logger->addEntry($e->getMessage());
}


