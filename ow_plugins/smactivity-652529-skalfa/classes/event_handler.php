<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 4/6/16
 * Time: 10:12 AM
 */

class SMACTIVITY_CLASS_EventHandler
{
    const FIELD_NAME = 'field_fb82fe18c534426af8d7a485d39ce984';

    private static $instance;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {

    }

    public function prepareUserListData(OW_Event $event)
    {
        $params = $event->getParams();
        $data = $event->getData();

        if ( !empty($data) && !empty($params['listName']) && in_array( $params['listName'], ['search_result', 'match_list'] ) )
        {
            $userList = array();

            foreach( $data as $key => $userData )
            {
                if ( !empty($userData['userId']) ) {
                    $userList[] = $userData['userId'];
                }
            }

            $questionsData = BOL_QuestionService::getInstance()->getQuestionData($userList, [self::FIELD_NAME]);

            foreach( $data as $key => $userData )
            {
                if ( !empty($userData['userId']) && !empty($questionsData[$userData['userId']]) ) {
                    $data[$key]['socialMediaActivity'] = $questionsData[$userData['userId']][self::FIELD_NAME];
                }
            }

            $event->setData($data);
        }
    }

    public function genericInit()
    {
        $em = OW::getEventManager();
        $em->bind('skandroid.user_list_prepare_user_data', array($this, 'prepareUserListData'));
        $em->bind('skadateios.user_list_prepare_user_data', array($this, 'prepareUserListData'));
    }
}