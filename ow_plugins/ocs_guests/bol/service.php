<?php

/**
 * Copyright (c) 2012, Oxwall CandyStore
 * All rights reserved.

 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.
 */

/**
 * Guests service class
 *
 * @author Oxwall CandyStore <plugins@oxcandystore.com>
 * @package ow.ow_plugins.ocs_guests.bol
 * @since 1.3.1
 */
final class OCSGUESTS_BOL_Service
{
    /**
     * @var OCSGUESTS_BOL_GuestDao
     */
    private $guestDao;

    /**
     * Class instance
     *
     * @var OCSGUESTS_BOL_Service
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    private function __construct()
    {
        $this->guestDao = OCSGUESTS_BOL_GuestDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return OCSGUESTS_BOL_Service
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
     * @param $userId
     * @param $guestId
     * @return bool
     */
    public function trackVisit( $userId, $guestId )
    {
        if ( !$userId || !$guestId || ($guestId == $userId) || BOL_AuthorizationService::getInstance()->isModerator($guestId) )
        {
            return false;
        }

        $guest = $this->guestDao->findGuest($userId, $guestId);
        $new = true;

        if ( $guest )
        {
            $guest->visitTimestamp = time();
            $new = (bool) $guest->viewed;
        }
        else
        {
            $guest = new OCSGUESTS_BOL_Guest();
            $guest->userId = $userId;
            $guest->guestId = $guestId;
            $guest->visitTimestamp = time();
        }

        $guest->viewed = 0;
        $this->guestDao->save($guest);

        $event = new OW_Event("guests.after_visit", array(
            "userId" => $guest->userId,
            "guestId" => $guest->guestId,
            "new" => $new
        ));

        OW::getEventManager()->trigger($event);

        return true;
    }

    /**
     * @param $userId
     * @param $page
     * @param $limit
     * @return array
     */
    public function findGuestsForUser( $userId, $page, $limit )
    {
        if ( !$userId )
        {
            return array();
        }

        $guests = $this->guestDao->findUserGuests($userId, $page, $limit);

        foreach ( $guests as &$g )
        {
            $g->visitTimestamp = UTIL_DateTime::formatDate($g->visitTimestamp, false);
        }

        return $guests;
    }
    
    /**
     * @param $userId
     * @param $page
     * @param $limit
     * @return array
     */
    public function findGuestsForUserList( $userId, $userList )
    {
        if ( !$userId || empty($userList) )
        {
            return array();
        }

        return $this->guestDao->getVisitStampByGuestIds($userId, $userList);
    }

    /**
     * @param $userId
     * @param $page
     * @param $limit
     * @return array
     */
    public function findGuestUsers( $userId, $page, $limit )
    {
        if ( !$userId )
        {
            return array();
        }

        $guests = $this->guestDao->findGuestUsers($userId, $page, $limit);

        return $guests;
    }

    /**
     * @param $userId
     * @return int
     */
    public function findNewGuestsCount( $userId )
    {
        if ( !$userId )
        {
            return 0;
        }

        return (int) $this->guestDao->countNewGuests($userId);
    }

    /**
     * @param $userId
     * @return int
     */
    public function countGuestsForUser( $userId )
    {
        return $this->guestDao->countUserGuests($userId);
    }

    /**
     * @return bool
     */
    public function checkExpiredGuests()
    {
        $months = (int) OW::getConfig()->getValue('ocsguests', 'store_period');
        $timestamp = $months * 30 * 24 * 60 * 60;

        $this->guestDao->deleteExpired($timestamp);

        return true;
    }

    /**
     * @param $userId
     * @return bool
     */
    public function deleteUserGuests( $userId )
    {
        $this->guestDao->deleteUserGuests($userId);

        return true;
    }

    public function getVisitedStampByGuestsIds( $userId, $guestIds )
    {
        $list =$this->guestDao->getVisitStampByGuestIds($userId, $guestIds);
        $result = array();
        
        foreach ( $list as $key => $value )
        {
            $result[$key] = UTIL_DateTime::formatDate($value, false);
        }
        
        unset($list);
        return $result;
    }

    public function findGuestsByGuestIds( $userId, $guestIds )
    {
        return $this->guestDao->findGuestsByGuestIds($userId, $guestIds);
    }

    public function setViewedStatusByGuestIds( $userId, $guestIds, $viewed = true )
    {
        return $this->guestDao->setViewedStatusByGuestIds($userId, $guestIds, $viewed);
    }
}
