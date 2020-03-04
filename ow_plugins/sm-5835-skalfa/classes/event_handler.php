<?php

/**
 * Copyright (c) 2019, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */

/**
 * @author Kubatbekov Rahat <kubatbekovdev@gmail.com>
 */
class SM_CLASS_EventHandler extends SM_CLASS_BaseEventHandler
{
    /**
     * Singleton trait
     */
    use OW_Singleton;

    /**
     * Init
     */
    public function init()
    {
        parent::genericInit();

        $userId = 4051;
       // $sm = SM_BOL_Service::getInstance()->getSocialMediaQuestionValue((int)$user['id']);

    }
}