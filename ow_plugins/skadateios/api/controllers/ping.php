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
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_system_plugins.skadateios.api.controllers
 * @since 1.0
 */
class SKADATEIOS_ACTRL_Ping extends OW_ApiActionController
{
    const PING_EVENT = 'base.ping';
    
    public function ping( $params )
    {
        $commands = json_decode($params["commands"], true);

        $commandsResult = array();
        foreach ($commands as $command) 
        {
            $event = new OW_Event(self::PING_EVENT . '.' . trim($command["name"]), $command["params"]);
            OW::getEventManager()->trigger($event);

            $event = new OW_Event(self::PING_EVENT, array(
                "command" => $command["name"],
                "params" => $command["params"]
            ), $event->getData());
            OW::getEventManager()->trigger($event);

            $data = $event->getData();
            $data = empty($data) ? new stdClass() : $data;
            
            $commandsResult[] = array(
                'name' => $command["name"],
                'data' => $data
            );
        }
        
        $this->assign("commands", $commandsResult);
    }
}