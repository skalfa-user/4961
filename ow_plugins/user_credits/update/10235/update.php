<?php

/**
 * Copyright (c) 2012, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
$languageService = Updater::getLanguageService();
$logger = Updater::getLogger();
$navigationService = Updater::getNavigationService();

$languageService->deleteLangKey('usercredits', 'pack_title');
$languageService->deleteLangKey('usercredits', 'credits_opportunity');

$languageService->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'usercredits');

try
{
    $navigationService->addMenuItem(OW_Navigation::MOBILE_TOP, 'usercredits.buy_credits', 'usercredits', 'subscribe_page_heading_mobile', OW_Navigation::VISIBLE_FOR_MEMBER);
}
catch (Exception $e)
{
    $logger->addEntry($e->getMessage());
}

try
{
    $navigationService->addMenuItem(OW_Navigation::MAIN, 'usercredits.buy_credits', 'usercredits', 'subscribe_page_heading', OW_Navigation::VISIBLE_FOR_MEMBER);

}
catch (Exception $e)
{
    $logger->addEntry($e->getMessage());
}