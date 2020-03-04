<?php

/**
 *
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow.ow_plugins.photver.bol
 * @since 1.8.4
 */

class PHOTVER_BOL_VerificationDao extends OW_BaseDao
{
    private $userDao;

    use OW_Singleton;

    protected function __construct()
    {
        parent::__construct();

        $this->userDao = BOL_UserDao::getInstance();
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     */
    public function getDtoClassName()
    {
        return 'PHOTVER_BOL_Verification';
    }

    /**
     * @see OW_BaseDao::getTableName()
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'photver_verification';
    }

    /**
     * Finding user list to approve
     *
     * @param $first
     * @param $count
     * @return array
     */
    public function findUserListToApprove( $first, $count )
    {
        $query = "SELECT `u`.*, `a`.`photoHash`, `a`.`updateStamp`
            FROM `" . $this->userDao->getTableName() . "` as `u`
            LEFT JOIN `" . $this->getTableName() . "` as `a` ON( `u`.`id` = `a`.`userId` )
            WHERE `a`.`id` IS NOT NULL AND `a`.`photoHash` IS NOT NULL
            ORDER BY `a`.`id` DESC
            LIMIT ?,?
        ";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    /**
     * Finding user count to approve
     *
     * @return mixed
     */
    public function findUserCountToApprove()
    {
        $query = "SELECT COUNT(*)
            FROM `" . $this->userDao->getTableName() . "` as `u`
            LEFT JOIN `" . $this->getTableName() . "` as `a` ON( `u`.`id` = `a`.`userId` )
            WHERE `a`.`id` IS NOT NULL AND `a`.`photoHash` IS NOT NULL
        ";

        return $this->dbo->queryForColumn($query);
    }
}