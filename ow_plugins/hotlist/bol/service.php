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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow.ow_plugins.hotlist.bol
 * @since 1.0
 */
class HOTLIST_BOL_Service {

    /**
     *
     * @var HOTLIST_BOL_UserDao
     */
    private $userDao;
    /**
     * Class instance
     *
     * @var HOTLIST_BOL_Service
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    protected function __construct() {
        $this->userDao = HOTLIST_BOL_UserDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return HOTLIST_BOL_Service
     */
    public static function getInstance() {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Add a message entries to a database.
     */
    public function addUser($userId)
    {
        $user = new HOTLIST_BOL_User();
        $user->userId = $userId;
        $user->timestamp = time();
        $user->expiration_timestamp = time() + OW::getConfig()->getValue('hotlist', 'expiration_time');

        $this->userDao->save($user);
    }

    public function deleteUser($userId)
    {
        return $this->userDao->deleteByUserId($userId);
    }

    public function getUserCount()
    {
        return $this->userDao->countAll();
    }

    public function clearExpiredUsers()
    {
        $userList = $this->userDao->findExpiredUsers();
        
        if (empty($userList))
        {
            return;
        }
        
//        foreach($userList as $user)
//        {
//            //Newsfeed
//            OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
//                'entityType' => 'add_to_hotlist',
//                'entityId' => $user->userId
//            )));
//        }
        $this->userDao->clearExpiredUsers();
    }

    public function getHotList( $start = 0, $count = null, $excludeList = array() )
    {
        return $this->userDao->findHotList($start, $count, $excludeList);
    }

    public function findUserById($userId)
    {
        return $this->userDao->findUserById($userId);
    }
}
