<?php

/**
 * Copyright (c) 2012, Oxwall CandyStore
 * All rights reserved.

 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.
 */

/**
 * /init.php
 *
 * @author Oxwall CandyStore <plugins@oxcandystore.com>
 * @package ow.ow_plugins.ocs_guests.mobile
 * @since 1.6.0
 */

OW::getRouter()->addRoute(
    new OW_Route('ocsguests_list', '/guests/list', 'OCSGUESTS_MCTRL_List', 'index')
);

OW::getRouter()->addRoute(
    new OW_Route('ocsguests_responder', '/guests/responder', 'OCSGUESTS_MCTRL_List', 'responder')
);

OCSGUESTS_MCLASS_EventHandler::getInstance()->init();