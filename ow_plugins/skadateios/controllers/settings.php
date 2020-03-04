<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_system_plugins.skadateios.controllers
 * @since 1.0
 */
class SKADATEIOS_CTRL_Settings extends ADMIN_CTRL_Abstract
{

    public function __construct()
    {
        parent::__construct();
    }
    
    public function init()
    {
        parent::init();
        
        $menus = array();
        $general = new BASE_MenuItem();
        $general->setLabel(OW::getLanguage()->text('skadateios', 'menu_settings_label'));
        $general->setUrl(OW::getRouter()->urlForRoute('skadateios.admin_settings'));
        $general->setKey('general');
        $general->setIconClass('ow_ic_gear_wheel');
        $general->setOrder(0);
        
        $menus[] = $general;
        $analytics = new BASE_MenuItem();
        $analytics->setLabel(OW::getLanguage()->text('skadateios', 'menu_analytics_label'));
        $analytics->setUrl(OW::getRouter()->urlForRoute('skadateios.admin_analytics'));
        $analytics->setKey('analytics');
        $analytics->setIconClass('ow_ic_info');
        $analytics->setOrder(1);
        $menus[] = $analytics;
        
        $ads = new BASE_MenuItem();
        $ads->setLabel(OW::getLanguage()->text('skadateios', 'menu_ads_label'));
        $ads->setUrl(OW::getRouter()->urlForRoute('skadateios.admin_ads'));
        $ads->setKey('ads');
        $ads->setIconClass('ow_ic_app');
        $ads->setOrder(2);
        $menus[] = $ads;

        $push = new BASE_MenuItem();
        $push->setLabel(OW::getLanguage()->text('skadateios', 'menu_push_notifications_label'));
        $push->setUrl(OW::getRouter()->urlForRoute('skadateios.admin_push'));
        $push->setKey('ads');
        $push->setIconClass('ow_ic_chat');
        $push->setOrder(3);
        $menus[] = $push;
        
        $this->addComponent('menu', new BASE_CMP_ContentMenu($menus));
    }

    public function index()
    {
        $language = OW::getLanguage();

        $configs = OW::getConfig()->getValues('skadateios');
        
        $configSaveForm = new SKADATEIOS_ConfigForm($configs);
        $this->addForm($configSaveForm);

        if ( OW::getRequest()->isPost() )
        {
            $configSaveForm->isValid($_POST);
            $configSaveForm->process();
            OW::getFeedback()->info($language->text('skadateios', 'settings_saved'));

            $this->redirect();
        }

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skadateios', 'admin_settings'));
        }
        
        $billingEnabled = (bool) $configs['billing_enabled'];
        
        $this->assign('billingEnabled', $billingEnabled);
        
        $script = " $('input[name=billing_enabled]').click(function() {
                    if( $(this).is( ':checked' ) )
                    {
                        $('tr.billing_enabled_settings').removeClass('ow_hidden');
                    }
                    else
                    {
                        $('tr.billing_enabled_settings').addClass('ow_hidden');
                    }
                } ) ";
        
        OW::getDocument()->addOnloadScript($script); 
    }
    
    public function analytics( array $params )
    {
        OW::getDocument()->setHeading(OW::getLanguage()->text('skadateios', 'admin_settings'));
        
        $form = new Form('analyticsSettings');
        
        $key = new TextField('flurry_api_key');
        $key->setValue(OW::getConfig()->getValue('skadateios', 'flurry_api_key'));
        $key->setLabel(OW::getLanguage()->text('skadateios', 'flurry_key_label'));
        $key->setDescription(OW::getLanguage()->text('skadateios', 'flurry_key_desc'));
        $form->addElement($key);
        
        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('admin', 'save_btn_label'));
        $form->addElement($submit);
        
        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            OW::getConfig()->saveConfig('skadateios', 'flurry_api_key', $form->getElement('flurry_api_key')->getValue());
            OW::getFeedback()->info(OW::getLanguage()->text('skadateios', 'settings_saved'));
            
            $this->redirect(OW::getRouter()->urlForRoute('skadateios.admin_analytics'));
        }
        
        $this->addForm($form);
    }
    
    public function ads( array $params )
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skadateios', 'admin_settings'));
        }
        $form = new Form('skadateios_ads');
        
        $key = new TextField('ads_key');
        $key->setValue(OW::getConfig()->getValue('skadateios', 'admod_api_key'));
        $key->setLabel(OW::getLanguage()->text('skadateios', 'ads_label'));
        
        $manualUrl = "#";
        
        $key->setDescription(OW::getLanguage()->text('skadateios', 'ads_desc', array(
            "manualUrl" => $manualUrl
        )));
        $form->addElement($key);
        
        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('admin', 'save_btn_label'));
        $form->addElement($submit);
        
        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            OW::getConfig()->saveConfig('skadateios', 'admod_api_key', $form->getElement('ads_key')->getValue());
            OW::getFeedback()->info(OW::getLanguage()->text('skadateios', 'settings_saved'));
            
            $this->redirect(OW::getRouter()->urlForRoute('skadateios.admin_ads'));
        }
        
        $this->addForm($form);
    }

    public function pushNotifications()
    {
        $form = new Form('skadateios_push');
        $form->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $pemFileName = SKADATEIOS_BOL_PushService::getInstance()->getCertificateFilePath();
        $pemFileUploaded = file_exists($pemFileName);

        $enabled = new CheckboxField("enabled");
        $enabled->setId("push-enabled");
        $enabled->setLabel(OW::getLanguage()->text('skadateios', 'push_enabled_label'));
        $enabled->setValue(OW::getConfig()->getValue('skadateios', 'push_enabled'));
        $form->addElement($enabled);

        $field = new RadioField('mode');
        $field->setLabel(OW::getLanguage()->text("skadateios", "push_mode_label"));
        $field->setOptions(array(
            "test" => OW::getLanguage()->text("skadateios", "push_mode_test"),
            "live" => OW::getLanguage()->text("skadateios", "push_mode_live")
        ));
        $field->setValue(OW::getConfig()->getValue('skadateios', 'push_mode'));
        $form->addElement($field);


        $pemFile = new FileField('pem_file');
        $pemFile->setLabel(OW::getLanguage()->text('skadateios', 'push_pem_file_label'));
        $pemFile->setDescription(OW::getLanguage()->text('skadateios', 'push_pem_file_desc'));
        $form->addElement($pemFile);

        $passPhrase = new TextField('pass_phrase');
        $passPhrase->setValue(OW::getConfig()->getValue('skadateios', 'push_pass_phrase'));
        $passPhrase->setLabel(OW::getLanguage()->text('skadateios', 'push_pass_phrase_label'));
        $passPhrase->setDescription(OW::getLanguage()->text('skadateios', 'push_pass_phrase_desc'));
        $form->addElement($passPhrase);

        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('admin', 'save_btn_label'));
        $form->addElement($submit);

        $this->addForm($form);

        $this->assign("pemFileUploaded", $pemFileUploaded);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            OW::getConfig()->saveConfig('skadateios', 'push_enabled', $data["enabled"]);

            if ( empty($data["enabled"]) )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('skadateios', 'settings_saved'));
                $this->redirect(OW::getRouter()->urlForRoute('skadateios.admin_push'));

                return;
            }

            if ( !$pemFileUploaded || !empty($_FILES[$pemFile->getName()]["tmp_name"]) )
            {
                $error = $this->validatePEMFile($pemFile);

                if ( !empty($error) )
                {
                    $pemFile->addError($error);

                    return;
                }

                move_uploaded_file($_FILES[$pemFile->getName()]['tmp_name'], $pemFileName);
            }

            OW::getConfig()->saveConfig('skadateios', 'push_pass_phrase', $data["pass_phrase"]);
            OW::getConfig()->saveConfig('skadateios', 'push_mode', $data["mode"]);

            OW::getFeedback()->info(OW::getLanguage()->text('skadateios', 'settings_saved'));
            $this->redirect(OW::getRouter()->urlForRoute('skadateios.admin_push'));
        }
    }

    private function validatePEMFile( FileField $field )
    {
        $language = OW::getLanguage();

        if ( empty($_FILES[$field->getName()]["tmp_name"]) )
        {
            return $language->text("base", "form_validator_required_error_message");
        }

        $file = $_FILES[$field->getName()];
        $message = BOL_FileService::getInstance()->getUploadErrorMessage($file['error']);

        if ( empty($message) &&  !UTIL_File::validate($file['name'], array("pem")) )
        {
            $message = OW::getLanguage()->text("skadateios", "wrong_pem_file");
        }

        return $message;
    }
}

class SKADATEIOS_ConfigForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct( $configs )
    {
        parent::__construct('configSaveForm');

        $language = OW::getLanguage();

        $field = new RadioField('itunes_mode');
        $field->setOptions(array(
            "test" => $language->text("skadateios", "itunes_mode_test"),
            "live" => $language->text("skadateios", "itunes_mode_live")
        ));
        
        $field->setValue($configs["itunes_mode"]);
        $this->addElement($field);

        $field = new CheckboxField('billing_enabled');
        $field->setValue($configs["billing_enabled"]);
        $this->addElement($field);
        
        $field = new TextField('itunes_secret');
        $field->addValidator(new ConfigRequireValidator());
        $field->setValue($configs["itunes_secret"]);
        $this->addElement($field);

        $promoUrl = new TextField('app_url');
        $promoUrl->setRequired();
        $promoUrl->addValidator(new UrlValidator());
        $promoUrl->setLabel($language->text('skadateios', 'app_url_label'));
        $promoUrl->setDescription($language->text('skadateios', 'app_url_desc'));
        $promoUrl->setValue($configs['app_url']);
        $this->addElement($promoUrl);

//        $smartBanner = new CheckboxField('smart_banner');
//        $smartBanner->setLabel($language->text('skadateios', 'smart_banner_label'));
//        $smartBanner->setDescription($language->text('skadateios', 'smart_banner_desc'));
//        $smartBanner->setValue($configs['smart_banner']);
//        $this->addElement($smartBanner);
        
        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $this->addElement($submit);
    }

    /**
     * Updates video plugin configuration
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();
        $config = OW::getConfig();
        
        $config->saveConfig('skadateios', 'billing_enabled', $values["billing_enabled"]);
        $config->saveConfig('skadateios', 'itunes_secret', $values["itunes_secret"]);
        $config->saveConfig('skadateios', 'itunes_mode', $values["itunes_mode"]);
        $config->saveConfig('skadateios', 'app_url', $values['app_url']);
        $config->saveConfig('skadateios', 'smart_banner', $values['smart_banner']);
    }
}

class ConfigRequireValidator extends RequiredValidator {
    
    public function getJsValidator()
    {
        return '{
        	validate : function( value ){
                    if ( $("input[name=billing_enabled]").is( ":checked" ) )
                    {
                        if( $.isArray(value) ){ if(value.length == 0  ) throw ' . json_encode($this->getError()) . "; }
                        else if( !value || $.trim(value).length == 0 ){ throw " . json_encode($this->getError()) . "; }
                    }
                },
        	getErrorMessage : function(){ return " . json_encode($this->getError()) . " }
        }";
    }
}