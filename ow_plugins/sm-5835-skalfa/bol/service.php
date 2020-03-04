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
class SM_BOL_Service
{
    /**
     * Singleton trait
     */
    use OW_Singleton;

    /**
     * Field social media
     */
    const SOCIAL_MEDIA = 'field_fb82fe18c534426af8d7a485d39ce984';

    /**
     * Class instance
     *
     * @var SM_BOL_Service
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    private function __construct()
    {
    }

    /**
     * Returns class instance
     *
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
     * Get social media question value
     *
     * @param $userId
     * @return int|mixed
     */
    public function getSocialMediaQuestionValue( $userId )
    {
        $smQuestionValues = BOL_QuestionService::getInstance()->findRealQuestionValues(SM_BOL_Service::SOCIAL_MEDIA);
        $smQuestionData = BOL_QuestionService::getInstance()->getQuestionData([$userId],[SM_BOL_Service::SOCIAL_MEDIA]);
        $smValue = 0;

        if ( $smQuestionValues && $smQuestionData )
        {
            foreach ( $smQuestionValues as $item )
            {
                $data = (array)$item;

                if ( !empty($smQuestionData[$userId][SM_BOL_Service::SOCIAL_MEDIA]) && $data['value'] == $smQuestionData[$userId][SM_BOL_Service::SOCIAL_MEDIA] )
                {
                    $smValue = array_fill(0,$data['sortOrder']+1,1);
                }
            }
        }

        return $smValue;
    }
}