<?php

/**
 * @author Sergey Pryadkin <GiperProger@gmail.com>
 * @package ow.ow_plugins.photver.bol
 * @since 1.8.4
 */


class PHOTVER_BOL_Verification extends OW_Entity
{
    /**
     * @var boolean
     */
    public $userId;

    /**
     * @var string
     */
    public $photoHash;

    /**
     * @var integer
     */
    public $updateStamp;

}