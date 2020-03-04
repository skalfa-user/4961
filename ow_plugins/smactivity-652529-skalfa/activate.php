<?php
/**
 * Created by PhpStorm.
 * User: jk
 * Date: 4/6/16
 * Time: 10:10 AM
 */

$dbo = BOL_QuestionService::getInstance()->findQuestionByName('field_fb82fe18c534426af8d7a485d39ce984');

if ( $dbo ) {
    BOL_QuestionService::getInstance()->deleteQuestion(array($dbo->id));
}

$dbo = BOL_QuestionService::getInstance()->findQuestionByName('field_fb82fe18c534426af8d7a485d39ce984');
if ( empty($dbo) )
{
    $dbo = new BOL_Question();

    $dbo = new BOL_Question();
    $dbo->accountTypeName = '';
    $dbo->removable = 0;
    $dbo->presentation = BOL_QuestionService::QUESTION_PRESENTATION_SELECT;
    $dbo->type = BOL_QuestionService::QUESTION_VALUE_TYPE_SELECT;
    $dbo->onEdit = 1;
    $dbo->onJoin = 1;
    $dbo->onSearch = 1;
    $dbo->onView = 1;
    $dbo->sectionName = 'f90cde5913235d172603cc4e7b9726e3';
    $dbo->name = 'field_fb82fe18c534426af8d7a485d39ce984';
    $dbo->sortOrder = 40;
    $dbo->removable = 1;

    BOL_QuestionService::getInstance()->saveOrUpdateQuestion($dbo);

    $list = array();

    $accountTypeList = BOL_QuestionService::getInstance()->findAllAccountTypes();

    if ( !empty($cache['accountTypes']) )
    {
        foreach( $accountTypeList as $accauntType )
        {
            /* @var $accauntType BOL_QuestionAccountType */
            if ( $cache['accountTypes'][$accauntType] == $accauntType  )
            {
                $list[$accauntType->name] = $accauntType->name;
            }
        }
    }

    if ( empty($list) )
    {
        foreach( $accountTypeList as $accauntType )
        {
            /* @var $accauntType BOL_QuestionAccountType */
            $list[$accauntType->name] = $accauntType->name;
        }
    }

    BOL_QuestionService::getInstance()->deleteQuestionValue('field_fb82fe18c534426af8d7a485d39ce984', 1);
    BOL_QuestionService::getInstance()->deleteQuestionValue('field_fb82fe18c534426af8d7a485d39ce984', 2);
    BOL_QuestionService::getInstance()->deleteQuestionValue('field_fb82fe18c534426af8d7a485d39ce984', 4);
    BOL_QuestionService::getInstance()->deleteQuestionValue('field_fb82fe18c534426af8d7a485d39ce984', 8);

    BOL_QuestionService::getInstance()->addQuestionValue( 'field_fb82fe18c534426af8d7a485d39ce984', 1, 'Under 1 hour a day on Social Media', 1 );
    BOL_QuestionService::getInstance()->addQuestionValue( 'field_fb82fe18c534426af8d7a485d39ce984', 2, 'Under 3 hour a day on Social Media', 2 );
    BOL_QuestionService::getInstance()->addQuestionValue( 'field_fb82fe18c534426af8d7a485d39ce984', 4, 'Under 5 hour a day on Social Media', 3 );
    BOL_QuestionService::getInstance()->addQuestionValue( 'field_fb82fe18c534426af8d7a485d39ce984', 8, 'Under 8 hour a day on Social Media', 4 );

    BOL_QuestionService::getInstance()->addQuestionListToAccountTypeList(array('field_fb82fe18c534426af8d7a485d39ce984'), $list);
    $questionLang = BOL_QuestionService::getInstance()->getQuestionLangKeyName(BOL_QuestionService::LANG_KEY_TYPE_QUESTION_LABEL, $dbo->name);
    $descriptionLang = BOL_QuestionService::getInstance()->getQuestionLangKeyName(BOL_QuestionService::LANG_KEY_TYPE_QUESTION_DESCRIPTION, $dbo->name);

    $defaultLanguage = BOL_LanguageService::getInstance()->findByTag('en');

    if ( !empty($defaultLanguage) )
    {
        try
        {
            BOL_LanguageService::getInstance()->addValue($defaultLanguage->id, 'base', $questionLang, 'Twifometer');
        }
        catch( Exception $ex )
        {

        }

        try
        {
            BOL_LanguageService::getInstance()->addValue($defaultLanguage->id, 'base', $descriptionLang, 'Time spent on Social Media');
        }
        catch( Exception $ex )
        {

        }
    }
}