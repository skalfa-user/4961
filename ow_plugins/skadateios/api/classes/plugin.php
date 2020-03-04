<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
class SKADATEIOS_ACLASS_Plugin
{
    /**
     * Class instance
     *
     * @var SKADATEIOS_CLASS_Plugin
     */
    private static $classInstance;

    /**
     * Class constructor
     */
    private function __construct()
    {

    }

    /**
     * Returns class instance
     *
     * @return SKADATEIOS_ACLASS_Plugin
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function isIOSRequest()
    {
        $uri = UTIL_Url::getRealRequestUri(OW_URL_HOME, $_SERVER['REQUEST_URI']);
        $uriParts = explode('/', $uri);
        
        return !in_array("android", $uriParts);
    }
    
    public function init()
    {
        $router = OW::getRouter();

        $router->addRoute(new OW_Route('skadateios.get_info', 'site/getInfo', 'SKADATEIOS_ACTRL_Base', 'siteInfo'));
        $router->addRoute(new OW_Route('skadateios.get_custom_page', 'site/getCustomPage', 'SKADATEIOS_ACTRL_Base', 'customPage'));
        $router->addRoute(new OW_Route('skadateios.user.authenticate', 'user/authenticate', 'SKADATEIOS_ACTRL_User', 'authenticate'));

        $router->addRoute(new OW_Route('skadateios.user.current.getInfo', 'user/me/getInfo', 'SKADATEIOS_ACTRL_User', 'getInfo'));
        $router->addRoute(new OW_Route('skadateios.user.getInfo', 'user/:userId/getInfo', 'SKADATEIOS_ACTRL_User', 'getInfo'));
        $router->addRoute(new OW_Route('skadateios.user.test', 'user/test', 'SKADATEIOS_ACTRL_User', 'test'));
        $router->addRoute(new OW_Route('skadateios.report', 'user/sendReport', 'SKADATEIOS_ACTRL_User', 'sendReport'));

        $router->addRoute(new OW_Route('skadateios.user.signout', 'user/signout', 'SKADATEIOS_ACTRL_User', 'signout'));
        $router->addRoute(new OW_Route('skadateios.user_set_location', 'user/setLocation', 'SKADATEIOS_ACTRL_User', 'setLocation'));
        $router->addRoute(new OW_Route('skadateios.user_get_questions', 'user/getQuestions', 'SKADATEIOS_ACTRL_User', 'getQuestions'));
        $router->addRoute(new OW_Route('skadateios.user_get_search_questions', 'user/getSearchQuestions', 'SKADATEIOS_ACTRL_User', 'getSearchQuestions'));
        $router->addRoute(new OW_Route('skadateios.user_save_question', 'user/saveQuestion', 'SKADATEIOS_ACTRL_User', 'saveQuestion'));
        $router->addRoute(new OW_Route('skadateios.block_user', 'user/blockUser', 'SKADATEIOS_ACTRL_User', 'blockUser'));
        $router->addRoute(new OW_Route('skadateios.avatar_change', 'user/avatarChange', 'SKADATEIOS_ACTRL_User', 'avatarChange'));
        $router->addRoute(new OW_Route('skadateios.avatar_from_poto', 'user/avatarFromPhoto', 'SKADATEIOS_ACTRL_User', 'avatarFromPhoto'));

        // Hot List
        $router->addRoute(new OW_Route('hotlist.user_list', 'hotlist/userList', 'SKADATEIOS_ACTRL_HotList', 'getList'));
        $router->addRoute(new OW_Route('hotlist.user_list.add', 'hotlist/userList/add', 'SKADATEIOS_ACTRL_HotList', 'addToList'));
        $router->addRoute(new OW_Route('hotlist.user_list.remove', 'hotlist/userList/remove', 'SKADATEIOS_ACTRL_HotList', 'removeFromList'));

        // Photo
        $router->addRoute(new OW_Route('photo.user_album_list', 'photo/userAlbumList', 'SKADATEIOS_ACTRL_Photo', 'getAlbumList'));
        $router->addRoute(new OW_Route('photo.user_photo_list', 'photo/userPhotoList', 'SKADATEIOS_ACTRL_Photo', 'getList'));
        $router->addRoute(new OW_Route('photo.album_photo_list', 'photo/albumPhotoList', 'SKADATEIOS_ACTRL_Photo', 'albumPhotoList'));
        $router->addRoute(new OW_Route('photo.delete_photos', 'photo/deletePhotos', 'SKADATEIOS_ACTRL_Photo', 'deletePhotos'));

        $router->addRoute(new OW_Route('photo.upload', 'photo/upload', 'SKADATEIOS_ACTRL_Photo', 'upload'));

        // Guests
        $router->addRoute(new OW_Route('guests.user_list', 'guests/userList', 'SKADATEIOS_ACTRL_Guests', 'getList'));

        // Matches
        $router->addRoute(new OW_Route('matches.user_list', 'matches/userList', 'SKADATEIOS_ACTRL_Matches', 'getList'));

        // SpeedMatch
        $router->addRoute(new OW_Route('speedmatch.get_user', 'speedmatch/getUser', 'SKADATEIOS_ACTRL_Speedmatch', 'getUser'));
        $router->addRoute(new OW_Route('speedmatch.get_criteria', 'speedmatch/getCriteria', 'SKADATEIOS_ACTRL_Speedmatch', 'getCriteria'));
        $router->addRoute(new OW_Route('speedmatch.like_user', 'speedmatch/likeUser', 'SKADATEIOS_ACTRL_Speedmatch', 'likeUser'));
        $router->addRoute(new OW_Route('speedmatch.skip_user', 'speedmatch/skipUser', 'SKADATEIOS_ACTRL_Speedmatch', 'skipUser'));

        // Bookmarks
        $router->addRoute(new OW_Route('bookmarks.mark_user', 'bookmarks/markUser', 'SKADATEIOS_ACTRL_Bookmarks', 'markUser'));
        $router->addRoute(new OW_Route('bookmarks.user_list', 'bookmarks/userList', 'SKADATEIOS_ACTRL_Bookmarks', 'getList'));

        // Winks
        $router->addRoute(new OW_Route('winks.send_wink', 'winks/sendWink', 'SKADATEIOS_ACTRL_Winks', 'sendWink'));
        $router->addRoute(new OW_Route('winks.send_wink_back', 'winks/sendWinkBack', 'SKADATEIOS_ACTRL_Winks', 'sendWinkBack'));
        $router->addRoute(new OW_Route('winks.accept_wink', 'winks/acceptWink', 'SKADATEIOS_ACTRL_Winks', 'acceptWink'));
        $router->addRoute(new OW_Route('winks.ignore_wink', 'winks/ignoreWink', 'SKADATEIOS_ACTRL_Winks', 'ignoreWink'));
        $router->addRoute(new OW_Route('winks.get_wink_requests', 'winks/getWinkRequests', 'SKADATEIOS_ACTRL_Winks', 'getWinkRequests'));

        // Billing
        $router->addRoute(new OW_Route('billing.subscribe_data', 'billing/subscribeData', 'SKADATEIOS_ACTRL_Billing', 'getSubscribeData'));
        $router->addRoute(new OW_Route('billing.suggest_options', 'billing/paymentOptions', 'SKADATEIOS_ACTRL_Billing', 'suggestPaymentOptions'));
        $router->addRoute(new OW_Route('billing.verify_sale', 'billing/verifySale', 'SKADATEIOS_ACTRL_Billing', 'verifySale'));

        // Mailbox
        $router->addRoute(new OW_Route('mailbox.get_unread_message_count', 'mailbox/getUnreadMessageCount', 'SKADATEIOS_ACTRL_Mailbox', 'getUnreadMessageCount'));
        $router->addRoute(new OW_Route('mailbox.user_list', 'mailbox/userList', 'SKADATEIOS_ACTRL_Mailbox', 'getList'));
        $router->addRoute(new OW_Route('mailbox.post_message', 'mailbox/postMessage', 'SKADATEIOS_ACTRL_Mailbox', 'postMessage'));
        $router->addRoute(new OW_Route('mailbox.post_reply_message', 'mailbox/postReplyMessage', 'SKADATEIOS_ACTRL_Mailbox', 'postReplyMessage'));
        $router->addRoute(new OW_Route('mailbox.get_new_messages', 'mailbox/getNewMessages', 'SKADATEIOS_ACTRL_Mailbox', 'getNewMessages'));
        $router->addRoute(new OW_Route('mailbox.get_messages', 'mailbox/getMessages', 'SKADATEIOS_ACTRL_Mailbox', 'getMessages'));
        $router->addRoute(new OW_Route('mailbox.get_history', 'mailbox/getHistory', 'SKADATEIOS_ACTRL_Mailbox', 'getHistory'));
        $router->addRoute(new OW_Route('mailbox.upload_attachment', 'mailbox/uploadAttachment', 'SKADATEIOS_ACTRL_Mailbox', 'uploadAttachment'));
        $router->addRoute(new OW_Route('mailbox.authorize', 'mailbox/authorize', 'SKADATEIOS_ACTRL_Mailbox', 'authorize'));
        $router->addRoute(new OW_Route('mailbox.mark_unread', 'mailbox/markUnread', 'SKADATEIOS_ACTRL_Mailbox', 'markUnread'));
        $router->addRoute(new OW_Route('mailbox.delete_conversation', 'mailbox/deleteConversation', 'SKADATEIOS_ACTRL_Mailbox', 'deleteConversation'));
        $router->addRoute(new OW_Route('mailbox.create_conversation', 'mailbox/createConversation', 'SKADATEIOS_ACTRL_Mailbox', 'createConversation'));
        $router->addRoute(new OW_Route('mailbox.find_user', 'mailbox/findUser', 'SKADATEIOS_ACTRL_Mailbox', 'findUser'));

        // Search
        $router->addRoute(new OW_Route('search.user_list', 'search/userList', 'SKADATEIOS_ACTRL_Search', 'getList'));

        // Sign Up
        $router->addRoute(new OW_Route('sign_up.question_list', 'signUp/questionList', 'SKADATEIOS_ACTRL_SignUp', 'questionList'));
        $router->addRoute(new OW_Route('sign_up.save', 'signUp/save', 'SKADATEIOS_ACTRL_SignUp', 'save'));

        $router->addRoute(new OW_Route('sign_up.try_log_in', 'signUp/tryLogIn', 'SKADATEIOS_ACTRL_SignUp', 'tryLogIn'));

        // Push Notifications
        $router->addRoute(new OW_Route('skadateios.register_device', 'notifications/registerDevice', 'SKADATEIOS_ACTRL_Notifications', 'registerDevice'));

        // Join
        $router->addRoute(new OW_Route('join.question_list', 'join/questionList', 'SKADATEIOS_ACTRL_SignUp', 'joinQuestionList'));
        $router->addRoute(new OW_Route('join.save_avatar', 'join/saveAvatar', 'SKADATEIOS_ACTRL_SignUp', 'saveAvatar'));
        $router->addRoute(new OW_Route('join.save', 'join/save', 'SKADATEIOS_ACTRL_SignUp', 'join'));
        $router->addRoute(new OW_Route('join.check_email', 'join/checkEmail', 'SKADATEIOS_ACTRL_SignUp', 'checkEmail'));
        $router->addRoute(new OW_Route('join.check_username', 'join/checkUsername', 'SKADATEIOS_ACTRL_SignUp', 'checkUsername'));
        $router->addRoute(new OW_Route('verification.verify_code', 'verification/verifyCode', 'SKADATEIOS_ACTRL_SignUp', 'verifyEmailCode'));
        $router->addRoute(new OW_Route('verification.resend_code', 'verification/resendCode', 'SKADATEIOS_ACTRL_SignUp', 'resendEmailCode'));

        // Email verification
        $router->addRoute(new OW_Route('base_email_verify', 'email-verify', 'BASE_CTRL_EmailVerify', 'index'));
        $router->addRoute(new OW_Route('base_email_verify_code_form', 'email-verify-form', 'BASE_CTRL_EmailVerify', 'verifyForm'));
        $router->addRoute(new OW_Route('base_email_verify_code_check', 'email-verify-check/:code', 'BASE_CTRL_EmailVerify', 'verify'));

        // Ping
        $router->addRoute(new OW_Route('base.ping', 'base/Ping', 'SKADATEIOS_ACTRL_Ping', 'ping'));

        $handler = new SKADATEIOS_ACLASS_EventHandler();
        $handler->init();

        // Exceptions
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_authenticated", "SKADATEIOS_ACTRL_User", "authenticate");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_authenticated", "SKADATEIOS_ACTRL_Base", "customPage");

        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_authenticated", "SKADATEIOS_ACTRL_Base", "siteInfo");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_approved", "SKADATEIOS_ACTRL_Base", "siteInfo");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_verified", "SKADATEIOS_ACTRL_Base", "siteInfo");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.suspended", "SKADATEIOS_ACTRL_Base", "siteInfo");

        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.suspended", "SKADATEIOS_ACTRL_User", "signout");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_approved", "SKADATEIOS_ACTRL_User", "signout");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_authenticated", "SKADATEIOS_ACTRL_User", "signout");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_verified", "SKADATEIOS_ACTRL_User", "signout");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_authenticated", "SKADATEIOS_ACTRL_Base", "siteInfo");

        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.suspended", "SKADATEIOS_ACTRL_Ping", "ping");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_approved", "SKADATEIOS_ACTRL_Ping", "ping");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_authenticated", "SKADATEIOS_ACTRL_Ping", "ping");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_verified", "SKADATEIOS_ACTRL_Ping", "ping");

        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_authenticated", "SKADATEIOS_ACTRL_SignUp");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_verified", "SKADATEIOS_ACTRL_SignUp", "verifyEmailCode");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_verified", "SKADATEIOS_ACTRL_SignUp", "resendEmailCode");

        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_approved", "SKADATEIOS_ACTRL_User", "saveQuestion");
        OW::getRequestHandler()->addCatchAllRequestsExclude("skadateios.not_approved", "SKADATEIOS_ACTRL_User", "getQuestions");
        
        // Desktop routes mocks
        OW::getRouter()->addRoute(new OW_Route('base_edit_user_datails', 'profile/:userId/edit/', 'BASE_CTRL_Edit', 'index'));
    }
}