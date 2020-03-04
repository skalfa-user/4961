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
 * Data Object for `skadateios_device` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.skadateios
 * @since 1.0
 */
class SKADATEIOS_BOL_Device extends OW_Entity
{
    /**
     * @var int
     */
    public $userId;

    /**
     * @var string
     */
    public $deviceToken;

    /**
     * @var array
     */
    public $properties;

    /**
     * @var int
     */
    public $timeStamp;

    public function getProperties()
    {
        if ( empty($this->properties) )
        {
            return array();
        }

        return json_decode($this->properties, true);
    }

    public function setProperties( array $props )
    {
        $this->properties = json_encode($props);
    }
}