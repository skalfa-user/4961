<?php

/**
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow.ow_plugins.photver.classes
 * @since 1.8.4
 */

class PHOTVER_CLASS_ImageValidator extends OW_Validator
{
    private $formId;
    private $errorMessageForNonImageFile;
    private $errorMessageForTooExceedImageFile;

    /**
     * Constructor
     *
     * @param integer $formId
     */
    public function __construct( $formId )
    {
        $language = OW::getLanguage();
        $pluginKey = PHOTVER_BOL_Service::PLUGIN_KEY;

        $this->formId = $formId;
        $this->errorMessageForNonImageFile = $language->text($pluginKey, 'error_message_for_non_image_file');
        $this->errorMessageForTooExceedImageFile = $language->text($pluginKey, 'error_message_for_too_exceed_image_file');

        $errorMessage = $language->text($pluginKey, 'error_message_for_too_exceed_image_file');;

        if( empty($errorMessage) )
        {
            $errorMessage = 'Image Validator Error!';
        }

        $this->setErrorMessage( $errorMessage );
    }

    /**
     * @see OW_Validator::isValid()
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isValid( $fieldName )
    {
        if( !in_array( $_FILES[$fieldName]['error'], array(UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE) ) ||
            !preg_match( '/[a-z0-9_\-]+\.(jpg|jpeg|png)/i', $_FILES[$fieldName]['name'] )
        )
        {
            return false;
        }

        return true;
    }

    /**
     * @see OW_Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        $postMaxSize = preg_replace( '/\D+/i', '', ini_get('post_max_size') ) * 1048576;

        $condition = '
            var allowedPostSize = ' . $postMaxSize . ';
            var fileInput = $(\'#' . $this->formId . '\').find(\'input[type="file"]\')[0];
            
            if (fileInput.files[0] !== undefined) {
                if (!fileInput.files[0].name.match(/\.(jpg|jpeg|png)$/i)) {
                    throw ' . json_encode( $this->errorMessageForNonImageFile ) . ';
                }
                else if (fileInput.files[0].size > allowedPostSize) {
                    throw ' . json_encode( $this->errorMessageForTooExceedImageFile )  . ';
                }
            }
        ';

        return '{
        	validate : function( value ) { ' . $condition . ' },
        	getErrorMessage : function() { return ' . json_encode( $this->getError() )  . ' }
        }';
    }
}