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
abstract class SM_CLASS_BaseEventHandler
{
    /**
     * Generic init
     */
    public function genericInit()
    {
        OW::getEventManager()->bind('skmobileapp.formatted_users_data', [$this, 'onGetUsersData']);
    }

    /**
     * On get users data
     */
    public function onGetUsersData( OW_Event $event )
    {
        $data = $event->getData();

        if ( !empty($data) )
        {
            foreach ($data as $key => $user) {

                $sm = SM_BOL_Service::getInstance()->getSocialMediaQuestionValue((int)$user['id']);

                if ( $sm ) {
                    $data[$key]['sm'] = $sm;
                }
            }
        }

        $event->setData($data);
    }
}
