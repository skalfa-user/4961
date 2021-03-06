<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

require_once dirname(__DIR__) . DS . "vendor" . DS . "autoload.php";

if ( SKADATEIOS_ACLASS_Plugin::getInstance()->isIOSRequest() )
{
    SKADATEIOS_ACLASS_Plugin::getInstance()->init();
}

SKADATEIOS_CLASS_EventHandler::getInstance()->genericInit();
