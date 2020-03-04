<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
OW::getRouter()->addRoute(
    new OW_Route('usercredits.buy_credits', 'user-credits/buy-credits', 'USERCREDITS_MCTRL_BuyCredits', 'subscribeCredits')
);

OW::getRouter()->addRoute(
    new OW_Route('usercredits_credit_info_mobile', 'usercredits/credit-info', 'USERCREDITS_MCTRL_BuyCredits', 'creditInfo')
);

OW::getRouter()->addRoute(
    new OW_Route('usercredits_pay_page', 'usercredits/pay-page/:packId/', 'USERCREDITS_MCTRL_BuyCredits', 'payPage')
);


USERCREDITS_MCLASS_EventHandler::getInstance()->init();

USERCREDITS_CLASS_EventHandler::getInstance()->genericInit();