<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
OW::getRouter()->addRoute(new OW_Route('protectedphotos.enter_password', 'protectedphotos/enter-password', 'PROTECTEDPHOTOS_CTRL_ProtectedPhoto', 'enterPassword'));
OW::getRouter()->addRoute(new OW_Route('protectedphotos.rsp.friend_list', 'protectedphotos/rsp/friendList', 'PROTECTEDPHOTOS_CTRL_ProtectedPhoto', 'rspFriendList'));

PROTECTEDPHOTOS_CLASS_EventHandler::getInstance()->init();
