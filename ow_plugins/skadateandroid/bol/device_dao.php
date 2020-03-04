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
 * @author Sardar Madumarov <madumarov@gmail.com> 
 * @since 1.8.1
 */
class SKANDROID_BOL_DeviceDao extends OW_BaseDao
{
    /**
     * Singleton instance.
     *
     * @var SKANDROID_BOL_DeviceDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return SKANDROID_BOL_DeviceDao
     */
    public static function getInstance()
    {
        if (self::$classInstance === null)
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return "SKANDROID_BOL_Device";
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . "skandroid_device";
    }

    /**
     * @param $deviceToken
     *
     * @return SKANDROID_BOL_Device
     */
    public function findByDeviceToken( $deviceToken )
    {
        $example = new OW_Example();
        $example->andFieldEqual("deviceToken", $deviceToken);

        return $this->findObjectByExample($example);
    }

    public function deleteByDeviceTokenList( array $tokens )
    {
        if ( empty($tokens) )
        {
            return;
        }

        $example = new OW_Example();
        $example->andFieldInArray("deviceToken", $tokens);

        $this->deleteByExample($example);
    }

    /**
     * @param $userId
     *
     * @return array
     */
    public function findByUserId( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual("userId", $userId);

        return $this->findListByExample($example);
    }
}