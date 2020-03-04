<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 * 
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com)
 * and is licensed under SkaDate Exclusive License by Skalfa LLC.
 * 
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

PHOTVER_CLASS_EventHandler::getInstance()->init();

OW::getRouter()->addRoute(new OW_Route('admin_photver_settings', 'photver/admin/index', 'PHOTVER_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('admin_photver_approval', 'photver/admin/approval', 'PHOTVER_CTRL_Admin', 'index') );
OW::getRouter()->addRoute(new OW_Route('user_photver_upload', 'photver/user/upload', 'PHOTVER_CTRL_UploadController', 'uploadPhoto') );
