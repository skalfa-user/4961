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
    new OW_Route('usercredits.buy_credits', 'user-credits/buy-credits', 'USERCREDITS_CTRL_BuyCredits', 'index')
);

USERCREDITS_CLASS_EventHandler::getInstance()->genericInit();