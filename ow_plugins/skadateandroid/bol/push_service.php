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

use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Notification;

/**
 * @author Sardar Madumarov <madumarov@gmail.com> 
 * @since 1.8.1
 */
class SKANDROID_BOL_PushService
{
    const TYPE_MESSAGE = "message";
    const TYPE_GUEST = "guest";
    const TYPE_WINK = "wink";
    const TYPE_SPEEDMATCH = "speedmatch";
    const PROPERTY_LANG = "lang";
    const NUMBER_OF_RETRIES = 3;
    const CNF_SERVER_API_KEY = "gmc_api_key";
    const GMC_MSG_MAX_LENGTH = 200;

    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return SKANDROID_BOL_PushService
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    /**
     * @var SKANDROID_BOL_DeviceDao
     */
    protected $deviceDao;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        $this->deviceDao = SKANDROID_BOL_DeviceDao::getInstance();
    }

    public function isPushEnabled()
    {
        return OW::getConfig()->getValue("skandroid", "push_enabled");
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
        $device = $this->deviceDao->findByDeviceToken($token);

        if ( $device === null )
        {
            $device = new SKANDROID_BOL_Device();
            $device->deviceToken = trim($token);
            $device->timeStamp = time();
        }

        $device->userId = $userId;
        $device->setProperties($properties);

        $this->deviceDao->save($device);
    }

    public function sendNotifiation( $userId, array $langData, array $params )
    {
        if ( !$this->isPushEnabled() || empty($langData["key"]) || empty($params["type"]) || empty($langData["titleKey"]) )
        {
            return;
        }

        $devices = $this->deviceDao->findByUserId($userId);

        if ( empty($devices) )
        {
            return;
        }

        $languageService = BOL_LanguageService::getInstance();

        /* @var $device SKANDROID_BOL_Device */
        foreach ( $devices as $device )
        {
            $deviceProps = $device->getProperties();
            $langDto = $languageService->getCurrent();

            if ( !empty($deviceProps[SKANDROID_BOL_PushService::PROPERTY_LANG]) )
            {
                $customLang = $languageService->findByTag($deviceProps[SKANDROID_BOL_PushService::PROPERTY_LANG]);

                if ( $customLang )
                {
                    $langDto = $customLang;
                }
            }

            if ( empty($langData["vars"]) )
            {
                $langData["vars"] = array();
            }

            $params["message"] = $languageService->getText($langDto->getId(), "skandroid", $langData["key"],
                $langData["vars"]);
            $params["title"] = $languageService->getText($langDto->getId(), "skandroid", $langData["titleKey"],
                $langData["vars"]);

            if( OW::getConfig()->configExists("skandroid", "use_firebase") )
            {
                $this->sendToFirebase($device->deviceToken, $params);
            }
            else
            {
                $this->sendToGms($params["type"], $device->deviceToken, $params);
            }
        }
    }

    private function sendToGms( $type, $deviceRegistrationId, $params )
    {
        $sender = new PHP_GCM\Sender(OW::getConfig()->getValue("skandroid", "gmc_api_key"));
        $message = new PHP_GCM\Message($type, $params);
        try
        {
            $result = $sender->send($message, $deviceRegistrationId, self::NUMBER_OF_RETRIES);
        }
        catch ( \InvalidArgumentException $e )
        {
            OW::getLogger()->addEntry("Empty registration id while sending push notification", "skandroid");
        }
        catch ( PHP_GCM\InvalidRequestException $e )
        {
            OW::getLogger()->addEntry("GMC returned bad response while sending push notification", "skandroid");
        }
        catch ( \Exception $e )
        {
            OW::getLogger()->addEntry("Sending push notification failed", "skandroid");
        }
    }

    private function sendToFirebase( $deviceRegistrationId, $params )
    {
        $server_key = OW::getConfig()->getValue("skandroid", "gmc_api_key");
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $notification = new Notification($params["title"], $params["message"]);

        switch ( $params["type"] )
        {
            case self::TYPE_MESSAGE:
                $notification->setIcon("ic_mail");
                break;

            case self::TYPE_WINK:
                $notification->setIcon("ic_wink");
                break;

            default:
                $notification->setIcon("ic_users");
        }

        $notification->setData($params);

        $message = new Message();
        $message->setPriority("high");
        $message->addRecipient(new Device($deviceRegistrationId));
        $message
            ->setNotification($notification)
            ->setData($params)
        ;

        $client->send($message);
    }
}
