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
 *
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow.ow_plugins.photver.bol
 * @since 1.8.4
 */
class PHOTVER_BOL_Service
{
    const PLUGIN_KEY = 'photver';
    const PHOTO_VERIFICATION_CONTROL_NAME = 'userIDPhoto';
    const IMAGE_PREFIX = 'photo_';
    const IMAGE_TMP_PREFIX = 'photo_tmp_';

    const EVENT_ON_PENDING_APPROVE = 'memberverification.on_pending_approve';
    const EVENT_ON_AFTER_APPROVE = 'memberverification.on_after_approve';
    const EVENT_ON_AFTER_DECLINE = 'memberverification.on_after_decline';
    const NOTIFICATION_PENDING_APPROVE = 'memberverification-pending-approve';
    const NOTIFICATION_AFTER_APPROVE = 'memberverification-after-approve';
    const NOTIFICATION_AFTER_DECLINE = 'memberverification-after-decline';


    //firebird constants

    const PENDING_APPROVAL = 0;
    const PHOTO_IS_VERIFIED = 1;
    const PHOTO_NOT_UPLOAD = 2;

    /**
     * @var PHOTVER_BOL_VerificationDao
     */
    protected $verificationDao;

    /**
     * @var BOL_UserDao
     */
    protected $userDao;

    /**
     * @var PHOTVER_BOL_ReasonsDao
     */
    protected $reasonsDao;


    /**
     * @var PHOTVER_BOL_Service
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return PHOTVER_BOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
        $this->verificationDao = PHOTVER_BOL_VerificationDao::getInstance();
        $this->userDao = BOL_UserDao::getInstance();
        $this->reasonsDao = PHOTVER_BOL_ReasonsDao::getInstance();
    }

    public function getPluginKey( )
    {
        return self::PLUGIN_KEY;
    }

    public function haveUserPassedSecondVerificationStep( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $example->andFieldIsNotNull('photoHash');

        return $this->verificationDao->countByExample($example) != 0 ? true : false;
    }

    public function markVerificationPhotoStepApp( $userId, $key )
    {
        $filePath = $this->getPhotoTmpDir() . $key . '.jpg';

        if (file_exists($filePath))
        {
            $this->markVerificationPhotoStep($userId, $filePath);

            @unlink($filePath);
        }
    }

    public function markVerificationPhotoStep( $userId, $photoTempPath )
    {
        $userFilesUrl = OW::getPluginManager()->getPlugin(self::PLUGIN_KEY)->getUserFilesDir();

        $fileName = self::IMAGE_PREFIX . uniqid() . '.jpg';


        $original = new UTIL_Image( $photoTempPath );
        $original->orientateImage()
            ->saveImage( $userFilesUrl . $fileName )
            ->destroy();

        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $verificationEntity = $this->verificationDao->findObjectByExample($example);

        if( empty($verificationEntity) )
        {
            $verificationEntity = new PHOTVER_BOL_Verification();
            $verificationEntity->userId = $userId;
        }

        $verificationEntity->photoHash = $fileName;
        $verificationEntity->updateStamp = time();

        $this->verificationDao->save($verificationEntity);
    }

    public function approveUser( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('id', $userId);

        $userEntity = $this->userDao->findObjectByExample($example);
        $userEntity->isVerified = 1;

        $this->userDao->save($userEntity);

        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        $this->verificationDao->deleteByExample($example);
    }

    public function declineUser( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        $this->verificationDao->deleteByExample($example);
    }

    public function addDeclineReason( $userId, $text )
    {
        $reason = new PHOTVER_BOL_Reasons();
        $reason->user_id = $userId;
        $reason->reason_text = $text;
    

        $this->reasonsDao->save($reason);
    }

    public function deleteDeclineReason( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('user_id', $userId);

        $this->reasonsDao->deleteByExample($example);
    }

    public function findDeclineReason( $userId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('user_id', $userId);

        $result = $this->reasonsDao->findObjectByExample($example);

        if( empty($result) )
        {
            return null;
        }

        return $result->reason_text;
    }

    public function removePhoto( $userId, $destinationPath )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        $verificationEntity = $this->verificationDao->findObjectByExample($example);

        if( !is_null($verificationEntity) )
        {
            unlink($destinationPath . $verificationEntity->photoHash);
        }
    }

    /**
     * Finding user list to approve
     *
     * @param integer $first
     * @param integer $count
     * @return mixed
     */
    public function findUserListToApprove( $first, $count )
    {
        return $this->verificationDao->findUserListToApprove( $first, $count );
    }

    /**
     * Finding user count to approve
     *
     * @return mixed
     */
    public function findUserCountToApprove()
    {
        return $this->verificationDao->findUserCountToApprove();
    }

    /**
     * Checking for user verification
     *
     * @param integer $userId
     * @return bool
     */
    public function isUserVerified( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('id', $userId);
        $example->andFieldEqual('isVerified', 1);

        return $this->userDao->countByExample($example) != 0 ? true : false;
    }

    public function getPhotoTmpDir()
    {
        $userFilesTmpDir = OW::getPluginManager()->getPlugin(self::PLUGIN_KEY)->getUserFilesDir() . 'tmp' . DS;

        if ( !dir($userFilesTmpDir) )
        {
            @mkdir($userFilesTmpDir);
            chmod($userFilesTmpDir, 0777);
        }

        return $userFilesTmpDir;
    }

    public function saveTmpPhoto($file)
    {
        $fileKey = self::IMAGE_TMP_PREFIX . uniqid();
        $fileName = $fileKey . '.jpg';

        $tmpPath = $this->getPhotoTmpDir() . $fileName;

        if ( move_uploaded_file($file, $tmpPath) )
        {
            $img = new UTIL_Image($tmpPath);
            $img->orientateImage()->saveImage();



            return [
                'url' => $url = OW::getPluginManager()->getPlugin(self::PLUGIN_KEY)->getUserFilesUrl() . '/tmp/' . $fileName,
                'key' => $fileKey
            ];
        }

        return [];
    }

    /**
     * Delete temp verify photos
     */
    public function deleteTempPhotos( )
    {
        $path = OW::getPluginManager()->getPlugin('photver')->getUserFilesDir() . 'tmp' . DS;

        if ( $handle = opendir($path) )
        {
            while ( false !== ($file = readdir($handle)) )
            {
                if ( !is_file($path.$file) )
                {
                    continue;
                }

                if ( time() - filemtime($path.$file) >= 60*60*24 )
                {
                    if ( !preg_match('/\.jpg$/i', $file) )
                    {
                        continue;
                    }

                    @unlink($path.$file);
                }
            }
        }
    }

}