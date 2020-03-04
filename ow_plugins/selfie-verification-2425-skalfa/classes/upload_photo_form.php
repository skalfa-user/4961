<?php

/**
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow_plugins.photver.classes
 * @since 1.8.4
 */

class PHOTVER_CLASS_UploadPhotoForm extends Form
{
    const FIELD_FILE = 'photo';
    const FIELD_CHECKBOX = 'recurring';
    const BUTTON_SUBMIT = 'send';

    public function __construct( $formName )
    {
        parent::__construct($formName);

        $language = OW::getLanguage();

        $this->setEnctype('multipart/form-data');
        $this->setEmptyElementsErrorMessage(null);

        $requiredValidator = new RequiredValidator();

        $photoField = new FileField(PHOTVER_BOL_Service::PHOTO_VERIFICATION_CONTROL_NAME);
        $photoField->setValue(self::FIELD_FILE);
        $photoField->addValidator($requiredValidator);
        $photoField->addValidator(new PHOTVER_CLASS_ImageValidator($this->getId()));
        $photoField->addAttribute('accept','.jpg,.png');
        $this->addElement($photoField);


        $submit = new Submit(self::BUTTON_SUBMIT);
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $this->addElement($submit);
    }

    public function process( $userId )
    {
        $service = PHOTVER_BOL_Service::getInstance();
        $service->markVerificationPhotoStep( $userId, $_FILES[PHOTVER_BOL_Service::PHOTO_VERIFICATION_CONTROL_NAME]['tmp_name'] );
        $service->deleteDeclineReason(OW::getUser()->getId());
    }
}