<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */



class PHOTVER_MCTRL_UploadController extends OW_MobileActionController
{
    /**
     * @var PHOTVER_BOL_Service
     */
    protected $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = PHOTVER_BOL_Service::getInstance();
    }

    public function uploadPhoto()
    {
        $uploadPhotoForm = OW::getClassInstance('PHOTVER_CLASS_UploadPhotoForm', 'upload_photo_form');

        $reasonText = $this->service->findDeclineReason(OW::getUser()->getId());
        $this->assign('reasonText', $reasonText);

        if( OW::getRequest()->isPost() && isset($_POST[$uploadPhotoForm::BUTTON_SUBMIT]) )
        {
            $uploadPhotoForm->process(OW::getUser()->getId());

            $this->redirect();
        }

        $this->addForm($uploadPhotoForm);
    }

}
