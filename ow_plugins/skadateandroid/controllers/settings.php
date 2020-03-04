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

/**
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.skandroid.controllers
 * @since 1.0
 */
class SKANDROID_CTRL_Settings extends ADMIN_CTRL_Abstract
{
    public function init()
    {
        parent::init();

        $handler = OW::getRequestHandler()->getHandlerAttributes();
        $menus = array();

        $general = new BASE_MenuItem();
        $general->setLabel(OW::getLanguage()->text('skandroid', 'menu_settings_label'));
        $general->setUrl(OW::getRouter()->urlForRoute('skandroid_admin_settings'));
        $general->setActive($handler[OW_RequestHandler::ATTRS_KEY_ACTION] === 'index');
        $general->setKey('general');
        $general->setIconClass('ow_ic_gear_wheel');
        $general->setOrder(0);
        $menus[] = $general;

        $analytics = new BASE_MenuItem();
        $analytics->setLabel(OW::getLanguage()->text('skandroid', 'menu_analytics_label'));
        $analytics->setUrl(OW::getRouter()->urlForRoute('skandroid_admin_analytics'));
        $analytics->setActive($handler[OW_RequestHandler::ATTRS_KEY_ACTION] === 'analytics');
        $analytics->setKey('analytics');
        $analytics->setIconClass('ow_ic_info');
        $analytics->setOrder(1);
        $menus[] = $analytics;

        $ads = new BASE_MenuItem();
        $ads->setLabel(OW::getLanguage()->text('skandroid', 'menu_ads_label'));
        $ads->setUrl(OW::getRouter()->urlForRoute('skandroid_admin_ads'));
        $ads->setActive($handler[OW_RequestHandler::ATTRS_KEY_ACTION] === 'ads');
        $ads->setKey('ads');
        $ads->setIconClass('ow_ic_app');
        $ads->setOrder(2);
        $menus[] = $ads;

        $push = new BASE_MenuItem();
        $push->setLabel(OW::getLanguage()->text('skandroid', 'menu_push_notifications_label'));
        $push->setUrl(OW::getRouter()->urlForRoute('skandroid.admin_push'));
        $push->setKey('push');
        $push->setIconClass('ow_ic_chat');
        $push->setOrder(3);
        $menus[] = $push;

        $this->addComponent('menu', new BASE_CMP_ContentMenu($menus));
    }

    public function index()
    {
        $language = OW::getLanguage();

        $configSaveForm = new ConfigSaveForm();
        $this->addForm($configSaveForm);


        $configs = OW::getConfig()->getValues('skandroid');

        if ( OW::getRequest()->isPost() && isset($_POST['save']) )
        {
            $res = $configSaveForm->process();
            OW::getFeedback()->info($language->text('skandroid', 'settings_saved'));

            $this->redirect();
        }

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skandroid', 'admin_settings'));
            OW::getDocument()->setHeadingIconClass('ow_ic_gear_wheel');
        }

        $billingEnabled = $configs['billing_enabled'] === '1' ? true : false;

        $configSaveForm->getElement('public_key')->setValue($configs['public_key']);
        $configSaveForm->getElement('billing_enabled')->setValue($billingEnabled);
        $this->assign('service_account_id', $configs['service_account_id']);

        $privateKey = $configs['service_account_private_key'];

        if ( !empty($privateKey) && mb_strlen($privateKey) > 100 )
        {
            $privateKey = mb_substr($privateKey, 0, 100);
        }

        $this->assign('service_account_private_key', $privateKey);

        $this->assign('billingEnabled', $billingEnabled);

        $script = " $('input[name=billing_enabled]').click(function() {
                    if( $(this).is( ':checked' ) )
                    {
                        $('.billing_enabled_settings').removeClass('ow_hidden');
                    }
                    else
                    {
                        $('.billing_enabled_settings').addClass('ow_hidden');
                    }
                } ) ";

        OW::getDocument()->addOnloadScript($script);
    }

    public function analytics( array $params )
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skandroid', 'admin_settings'));
        }

        $form = new Form('skandroid_analytics');

        $key = new TextField('analytics_key');
        $key->setRequired();
        $key->setValue(OW::getConfig()->getValue('skandroid', 'analytics_api_key'));
        $key->setLabel(OW::getLanguage()->text('skandroid', 'analytics_label'));
        $key->setDescription(OW::getLanguage()->text('skandroid', 'analytics_desc'));
        $form->addElement($key);

        $submit = new Submit('analytics_submit');
        $submit->setValue(OW::getLanguage()->text('skandroid', 'analytics_submit'));
        $form->addElement($submit);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            OW::getConfig()->saveConfig('skandroid', 'analytics_api_key', $form->getElement('analytics_key')->getValue());
            OW::getFeedback()->info(OW::getLanguage()->text('skandroid', 'settings_saved'));
        }

        $this->addForm($form);
    }

    public function ads( array $params )
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skandroid', 'admin_settings'));
        }

        $form = new Form('skandroid_ads');

        $key = new TextField('ads_key');
        $key->setRequired();
        $key->setValue(OW::getConfig()->getValue('skandroid', 'ads_api_key'));
        $key->setLabel(OW::getLanguage()->text('skandroid', 'ads_label'));
        $key->setDescription(OW::getLanguage()->text('skandroid', 'ads_desc'));
        $form->addElement($key);

        $submit = new Submit('ads_submit');
        $submit->setValue(OW::getLanguage()->text('skandroid', 'ads_submit'));
        $form->addElement($submit);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            OW::getConfig()->saveConfig('skandroid', 'ads_api_key', $form->getElement('ads_key')->getValue());
            OW::getFeedback()->info(OW::getLanguage()->text('skandroid', 'settings_saved'));
        }

        $this->addForm($form);
    }

    public function pushNotifications()
    {
        $config = OW::getConfig();
        $language = OW::getLanguage();

        $form = new Form("push");

        $enabled = new CheckboxField("enabled");
        $enabled->setId("push-enabled");
        $enabled->setLabel($language->text("skandroid", "push_enabled_label"));
        $enabled->setValue($config->getValue("skandroid", "push_enabled"));
        $form->addElement($enabled);


        $provider = new RadioField("provider");
        $provider->setId("push-provider");
        $provider->setLabel($language->text("skandroid", "push_provider_label"));
        $provider->setOptions(["Google Cloud Messaging", "Firebase ({$language->text("skandroid", "firebase_warning")})"]);
        $provider->setValue(intval($config->getValue("skandroid", "use_firebase")));
        $form->addElement($provider);

        $apiKey = new TextField("gmc_api_key");
        $apiKey->setValue($config->getValue("skandroid", "gmc_api_key"));
        $apiKey->setLabel($language->text("skandroid", "gmc_api_key_label"));
        $apiKey->setDescription($language->text("skandroid", "gmc_api_key_desc"));
        $form->addElement($apiKey);

        $submit = new Submit("save");
        $submit->setValue($language->text("admin", "save_btn_label"));
        $form->addElement($submit);
        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if ( !empty($data["enabled"]) && empty($data["gmc_api_key"]) )
            {
                OW::getFeedback()->error($language->text("skandroid", "invalid_api_key"));
                $this->redirect(OW::getRouter()->urlForRoute("skandroid.admin_push"));
            }

            $config->saveConfig("skandroid", "push_enabled", !empty($data["enabled"]));
            $config->saveConfig("skandroid", "gmc_api_key", $data["gmc_api_key"]);
            $config->saveConfig("skandroid", "use_firebase", boolval($data["provider"]));

            OW::getFeedback()->info($language->text("skandroid", "settings_saved"));
            $this->redirect(OW::getRouter()->urlForRoute("skandroid.admin_push"));
        }
    }
}

class ConfigSaveForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct('configSaveForm');

        $language = OW::getLanguage();


        $this->setEnctype(self::ENCTYPE_MULTYPART_FORMDATA);

        $field = new TextField('public_key');
        $field->addValidator(new ConfigRequireValidator());
        $this->addElement($field);

        $field = new FileField('service_account_info');
        $field->addValidator(new ServiceAccountRequireValidator());
        $this->addElement($field);

        $field = new CheckboxField('billing_enabled');
        $this->addElement($field);

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $this->addElement($submit);

        $promoUrl = new TextField('app_url');
        $promoUrl->setRequired();
        $promoUrl->addValidator(new UrlValidator());
        $promoUrl->setLabel($language->text('skandroid', 'app_url_label'));
        $promoUrl->setDescription($language->text('skandroid', 'app_url_desc'));
        $promoUrl->setValue(OW::getConfig()->getValue('skandroid', 'app_url'));
        $this->addElement($promoUrl);

//        $smartBanner = new CheckboxField('smart_banner');
//        $smartBanner->setLabel($language->text('skandroid', 'smart_banner_label'));
//        $smartBanner->setDescription($language->text('skandroid', 'smart_banner_desc'));
//        $smartBanner->setValue(OW::getConfig()->getValue('skandroid', 'smart_banner'));
//        $this->addElement($smartBanner);
    }

    /**
     * Updates video plugin configuration
     *
     * @return boolean
     */
    public function process()
    {
        $config = OW::getConfig();
        if ( !$config->configExists('skandroid', 'service_account_id') )
        {
            $config->addConfig('skandroid', 'service_account_id', '');
        }

        if ( !$config->configExists('skandroid', 'service_account_private_key') )
        {
            $config->addConfig('skandroid', 'service_account_private_key', '');
        }

        $field = new TextField('service_account_id'); // Service account ID
        $field->addValidator(new ConfigRequireValidator());
        $this->addElement($field);

        $field = new Textarea('service_account_private_key');
        $field->addValidator(new ConfigRequireValidator());
        $this->addElement($field);

        if ( $_FILES['service_account_info'] ) {
            $content = file_get_contents($_FILES['service_account_info']['tmp_name']);

            $list = json_decode($content, true);

            if ( !empty($list['private_key']) || !empty($list['client_email']) )
            {
                $config->saveConfig('skandroid', 'service_account_id', $list['client_email']);
                $config->saveConfig('skandroid', 'service_account_private_key', $list['private_key']);
            }
        }

        $config->saveConfig('skandroid', 'public_key', !empty($_POST['public_key']) ? $_POST['public_key'] : "");
        $config->saveConfig('skandroid', 'billing_enabled',
            !empty($_POST['billing_enabled']) && $_POST['billing_enabled'] ? '1' : '0');

        $config->saveConfig('skandroid', 'app_url', $_POST['app_url']);
        $config->saveConfig('skandroid', 'smart_banner', $_POST['smart_banner']);

        return array('result' => true);
    }
}

class ConfigRequireValidator extends RequiredValidator
{

    public function getJsValidator()
    {
        return '{
        	validate : function( value ){
                    if ( $("input[name=billing_enabled]").is( ":checked" ) )
                    {
                        if( $.isArray(value) ){ if(value.length == 0  ) throw ' . json_encode($this->getError()) . "; return;}
                        else if( !value || $.trim(value).length == 0 ){ throw " . json_encode($this->getError()) . "; }
                    }
                },
        	getErrorMessage : function(){ return " . json_encode($this->getError()) . " }
        }";
    }
}

class ServiceAccountRequireValidator extends RequiredValidator
{
    /**
     * File extension Validator
     *
     * @author Alex Ermashev <alexermashev@gmail.com>
     * @package ow_core
     * @since 1.8.4
     */

    protected $disallowedExtensions = array(
        'php*',
        'phtml'
    );

    public function isValid( $value )
    {
        $configs = OW::getConfig()->getValues('skandroid');

        if ( empty($_FILES['service_account_info'])
            && ( empty($configs['service_account_id'])
            || empty($configs['service_account_private_key']) ) )
        {
            return false;
        }

        $values = explode(PHP_EOL, $_FILES['service_account_info']['name']);

        foreach($values as $extension)
        {
            foreach($this->disallowedExtensions as $disallowedExtensions)
            {
                if ( preg_match('/' . $disallowedExtensions . '/i', $extension) )
                {
                    $this->errorMessage = OW::getLanguage()->text('admin', 'wrong_file_extension', array(
                        'extensions' => implode(',', $this->disallowedExtensions)
                    ));
                    return false;
                }
            }
        }

        if ( $_FILES['service_account_info']['error'] != UPLOAD_ERR_OK ) {
            $message = BOL_FileService::getInstance()->getUploadErrorMessage($_FILES['service_account_info']['error']);
            $this->setErrorMessage($message);
            return false;
        }

        $content = file_get_contents($_FILES['service_account_info']['tmp_name']);

        $list = @json_decode($content, true);

        if ( empty($list['private_key']) || empty($list['client_email']) )
        {
            $this->errorMessage = OW::getLanguage()->text('skadate_android', 'invalid_service_account_info');
            return false;
        }

        return true;
    }

    public function getJsValidator()
    {
        return ' {
        	validate : function( value ){
        	        console.log(value);
                    if ( $("input[name=billing_enabled]").is( ":checked" )
                        && ( !$("#service_account_id").get(0) || !$("#service_account_id").get(0) ) )
                    {
                        if( $.isArray(value) ){ if(value.length == 0  ) throw ' . json_encode($this->getError()) . '; return;}
                        else if( !value || $.trim(value).length == 0 ){ throw ' . json_encode($this->getError()) . '; }
                    }
                },
        	getErrorMessage : function(){ return ' . json_encode($this->getError()) . ' }
        } ';
    }
}

