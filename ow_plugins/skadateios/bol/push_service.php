<?php

use Apple\ApnPush\Certificate\Certificate;
use Apple\ApnPush\Notification;
use Apple\ApnPush\Notification\Connection as NotificationConnection;
use Apple\ApnPush\Feedback\Connection as FeedbackConnection;
use Apple\ApnPush\Feedback\Feedback;
use Apple\ApnPush\Notification\Message;
use Apple\ApnPush\Notification\MessageInterface;
use Apple\ApnPush\Feedback\Device;

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * Apple Push Notifications Service
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.skadateios
 * @since 1.0
 */
class SKADATEIOS_BOL_PushService
{
    const PROPERTY_LANG = "lang";
    const CERT_FILE_NAME = "apns_cert.pem";
    const MESSAGE_LENGTH = 200;

    const TYPE_MESSAGE = "message";
    const TYPE_GUEST = "guest";
    const TYPE_WINK = "wink";
    const TYPE_SPEEDMATCH = "speedmatch";

    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return SKADATEIOS_BOL_PushService
     */
    public static function getInstance()
    {
        if (null === self::$classInstance) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @var SKADATEIOS_BOL_DeviceDao
     */
    protected $deviceDao;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        $this->deviceDao = SKADATEIOS_BOL_DeviceDao::getInstance();
    }

    public function isPushEnabled()
    {
        return OW::getConfig()->getValue("skadateios", "push_enabled");
    }

    public function isInSandBoxMode()
    {
        return OW::getConfig()->getValue("skadateios", "push_mode") == "test";
    }

    public function getCertificateFilePath()
    {
        $dir = OW::getPluginManager()->getPlugin("skadateios")->getPluginFilesDir();

        return $dir . self::CERT_FILE_NAME;
    }

    public function getCertificatePassPhrase()
    {
        return OW::getConfig()->getValue("skadateios", "push_pass_phrase");
    }

    /**
     * Returns server certificate object
     *
     * @return Certificate
     */
    protected function getCertificate()
    {
        $certFile = $this->getCertificateFilePath();
        $passPhrase = $this->getCertificatePassPhrase();

        return new Certificate($certFile, $passPhrase);
    }

    /**
     * Creates a notification service
     *
     * @return Notification
     */
    protected function getNotificationService()
    {
        $certificate = $this->getCertificate();
        $connection = new NotificationConnection($certificate, $this->isInSandBoxMode());
        $notificationService = new Notification($connection);

        return $notificationService;
    }

    /**
     * Creates a notification message
     *
     * @param $deviceToken
     * @param $message
     * @param array $data
     *
     * @return MessageInterface
     */
    public function createNotification( $deviceToken, $message, $data = array() )
    {
        $message = UTIL_String::truncate(strip_tags($message), self::MESSAGE_LENGTH, "...");

        $message = new Message($deviceToken, $message);
        $message->setCustomData($data);

        return $message;
    }

    /**
     * Sends the message to APNS
     *
     * @param MessageInterface $message
     *
     * @return bool
     */
    public function sendNotification( MessageInterface $message )
    {
        if ( !$this->isPushEnabled() )
        {
            return;
        }

        try
        {
            return $this->getNotificationService()->send($message);
        }
        catch( Exception $e )
        {
            OW::getLogger("APNS")->addEntry(json_encode($e), "apns.send");

            return false;
        }

    }

    public function sendToUserDevices( $userId, $message, $data = array() )
    {
        if ( !$this->isPushEnabled() )
        {
            return;
        }

        $devices = $this->findUserDevices($userId);
        $langInfo = array();

        if ( is_array($message) && !empty($message["key"]) )
        {
            list($langInfo["prefix"], $langInfo["key"]) = explode("+", $message["key"]);
            $langInfo["vars"] = empty($message["vars"]) ? array() : $message["vars"];
        }

        /**
         * @var $device SKADATEIOS_BOL_Device
         */
        foreach ( $devices as $device )
        {
            $localizedMessage = $message;

            if ( !empty($langInfo) )
            {
                $deviceProps = $device->getProperties();

                if ( !empty($deviceProps[self::PROPERTY_LANG]) ) // TODO: refactor the `IF clause` when appropriate methods will be ready
                {
                    $languageDto = BOL_LanguageService::getInstance()->findByTag($deviceProps[self::PROPERTY_LANG]);

                    if ( !empty($languageDto) && $languageDto->status == "active" )
                    {
                        BOL_LanguageService::getInstance()->setCurrentLanguage($languageDto);
                    }
                }

                $localizedMessage = OW::getLanguage()->text($langInfo["prefix"], $langInfo["key"], $langInfo["vars"]);
            }

            $notification = $this->createNotification($device->deviceToken, $localizedMessage, $data);
            $this->sendNotification($notification);
        }
    }

    /**
     * @param string $token
     * @param int $userId
     * @param array $properties
     *
     * @return SKADATEIOS_BOL_Device
     */
    public function registerDevice( $token, $userId, $properties = array() )
    {
        $device = $this->findDevice($token);

        if ( $device === null )
        {
            $device = new SKADATEIOS_BOL_Device();
            $device->deviceToken = trim($token);
            $device->timeStamp = time();
        }

        $device->userId = $userId;
        $device->setProperties($properties);

        return $this->deviceDao->save($device);
    }

    public function unregisterDevices( $tokens )
    {
        $this->deviceDao->deleteByDeviceTokenList($tokens);
    }

    public function unregisteUserDevices( $userId )
    {
        $devices = $this->findUserDevices($userId);
        $tokens = array();

        /**
         * @var $device SKADATEIOS_BOL_Device
         */
        foreach ( $devices as $device )
        {
            $tokens[] = $device->deviceToken;
        }

        $this->deviceDao->deleteByDeviceTokenList($tokens);
    }

    /**
     * @param string $token
     *
     * @return SKADATEIOS_BOL_Device
     */
    public function findDevice( $token )
    {
        return $this->deviceDao->findByDeviceToken($token);
    }

    /**
     * @param $userId
     *
     * @return array
     */
    public function findUserDevices( $userId )
    {
        return $this->deviceDao->findByUserId($userId);
    }


    public function getInvalidDeviceTokens()
    {
        if ( !$this->isPushEnabled() )
        {
            return array();
        }

        $certificate = $this->getCertificate();

        $connection = new FeedbackConnection($certificate, $this->isInSandBoxMode());
        $feedback = new Feedback($connection);

        $devices = $feedback->getInvalidDevices();

        $tokens = array();

        /**
         * @var $device Device
         */
        foreach ( $devices as $device )
        {
            $tokens[] = $device->getDeviceToken();
        }

        return $tokens;
    }
}