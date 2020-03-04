<?php

/**
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow.ow_plugins.photver.classes
 * @since 1.8.4
 */
class  PHOTVER_CLASS_AdminApprovalForm extends Form
{
    const USER_IDS = 'users';
    const BUTTON_APPROVE = 'approve';
    const BUTTON_DECLINE = 'decline';

    public function __construct( $formName, $urlResponder )
    {
        parent::__construct($formName);

        $this->setAction($urlResponder);
    }

    public function getFormButtons()
    {
        $language = OW::getLanguage();
        $service =  PHOTVER_BOL_Service::getInstance();

        $buttons = array();

        $buttons[] = array(
            'id' => 'approve_request',
            'name' => 'approve_request',
            'label' => $language->text($service::PLUGIN_KEY, 'admin_approve_button'),
            'class' => 'ow_mild_red'
        );
        $buttons[] = array(
            'id' => 'decline_request',
            'name' => 'decline_request',
            'label' => $language->text($service::PLUGIN_KEY, 'admin_decline_button'),
            'class' => 'ow_mild_red'
        );



        return $buttons;
    }

    public function processApprove()
    {
        if( $this->isValid($_POST) )
        {
            $pluginManager = OW::getPluginManager();
            $service =  PHOTVER_BOL_Service::getInstance();
            $userFilesDir = $pluginManager->getPlugin($service::PLUGIN_KEY)->getUserFilesDir();

            foreach( $_POST[self::USER_IDS] as $userId )
            {
                $service->removePhoto($userId, $userFilesDir);
                $service->approveUser($userId);
            }

            return true;
        }
        else
        {
            return false;
        }
    }

    public function processDecline()
    {
        if( $this->isValid($_POST) )
        {
            $pluginManager = OW::getPluginManager();
            $service =  PHOTVER_BOL_Service::getInstance();
            $userFilesDir = $pluginManager->getPlugin($service::PLUGIN_KEY)->getUserFilesDir();


            foreach( $_POST[self::USER_IDS] as $userId )
            {
                $service->addDeclineReason($userId, $_POST['decline_reason']);
                $service->removePhoto($userId, $userFilesDir);
                $service->declineUser($userId);
            }

            return true;
        }
        else
        {
            return false;
        }
    }
}