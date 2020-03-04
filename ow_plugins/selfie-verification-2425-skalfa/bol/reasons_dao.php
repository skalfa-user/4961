<?php

/**
 *
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow.ow_plugins.photver.bol
 * @since 1.8.4
 */


class PHOTVER_BOL_ReasonsDao extends OW_BaseDao
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
        return 'PHOTVER_BOL_Reasons';
    }

    /**
     * @see OW_BaseDao::getTableName()
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'photver_reasons';
    }
}