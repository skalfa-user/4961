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
    new OW_Route('usercredits.admin', 'admin/plugins/user-credits/', 'USERCREDITS_CTRL_Admin', 'index')
);

OW::getRouter()->addRoute(
    new OW_Route('usercredits.admin_settings', 'admin/plugins/user-credits/settings', 'USERCREDITS_CTRL_Admin', 'settings')
);

OW::getRouter()->addRoute(
    new OW_Route('usercredits.admin_packs', 'admin/plugins/user-credits/packs', 'USERCREDITS_CTRL_Admin', 'packs')
);

OW::getRouter()->addRoute(
    new OW_Route('usercredits.buy_credits', 'user-credits/buy-credits', 'USERCREDITS_CTRL_BuyCredits', 'index')
);

OW::getRouter()->addRoute(
    new OW_Route('usercredits.history', 'user-credits/history', 'USERCREDITS_CTRL_Credits', 'history')
);

if ( OW::getPluginManager()->getPlugin('usercredits')->getDto()->build >= 9913 ) {
    USERCREDITS_CLASS_EventHandler::getInstance()->init();
}