<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 * 
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com)
 * and is licensed under SkaDate Exclusive License by Skalfa LLC.
 * 
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

class SKANDROID_ACLASS_Plugin
{
    /**
     * Class instance
     *
     * @var SKANDROID_ACLASS_Plugin
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
     * @return SKANDROID_ACLASS_Plugin
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function isAndroidRequest()
    {
        return in_array("android", explode("/", UTIL_Url::getRealRequestUri(OW_URL_HOME, $_SERVER['REQUEST_URI'])));
    }
    
    public function init()
    {
        $service = SKANDROID_ABOL_Service::getInstance();
        $service->addRoute('skandroid.get_info', 'site/get-info', 'SKANDROID_ACTRL_Base', 'siteInfo');
        $service->addRoute('skandroid.user_get_info', 'site/user-get-info', 'SKANDROID_ACTRL_Base', 'userSiteInfo');

        /* ***************************** JK *************************** */

        $service->addRoute('skandroid.user_get_questions', 'user/get-questions/:id', 'SKANDROID_ACTRL_User', 'getQuestions');
        //
        $service->addRoute('skandroid.user_save_question', 'user/saveQuestion', 'SKANDROID_ACTRL_User', 'saveQuestion');
        // flags
        $service->addRoute('skandroid.report', 'user/sendReport', 'SKANDROID_ACTRL_User', 'sendReport');

        // Search
        $service->addRoute('skandroid.search.user_list', 'search/user-list', 'SKANDROID_ACTRL_Search', 'getList');
        $service->addRoute('skandroid.user_get_search_questions', 'user/get-search-questions', 'SKANDROID_ACTRL_User', 'getSearchQuestions');

        // Guests
        $service->addRoute('skandroid.guests.user_list', 'guests/userList', 'SKANDROID_ACTRL_Guests', 'getList');

        // Billing
        $service->addRoute('billing.subscribe_data', 'billing/subscribeData', 'SKANDROID_ACTRL_Billing', 'getSubscribeData');
        $service->addRoute('billing.suggest_options', 'billing/payment-options', 'SKANDROID_ACTRL_Billing', 'suggestPaymentOptions');
        $service->addRoute('billing.verify_sale', 'billing/verifySale', 'SKANDROID_ACTRL_Billing', 'verifySale');
        $service->addRoute('billing.preverify_sale', 'billing/preverifySale', 'SKANDROID_ACTRL_Billing', 'preverifySale');
        $service->addRoute('billing.set_trial_plan', 'billing/setTrialPlan', 'SKANDROID_ACTRL_Billing', 'setTrialPlan');

        // Bookmarks
        $service->addRoute('skandroid.bookmarks.user_list', 'bookmarks/userList', 'SKANDROID_ACTRL_Bookmarks', 'getList');
        $service->addRoute('skandroid.get-auth-status', 'base/authorization-action-status/', 'SKANDROID_ACTRL_Base', 'getAutorizationAction');

        /* ******************************* End **************************** */

        // Photo
        $service->addRoute('skandroid.photo.user_photo_list', 'photo/user-photo-list/:id', 'SKANDROID_ACTRL_Photo', 'getList');
        $service->addRoute('skandroid.photo.user_album_list', 'photo/user-album-list/:userId', 'SKANDROID_ACTRL_Photo', 'getAlbumList');
        $service->addRoute('skandroid.photo.album_photo_list', 'photo/album-photo-list/:albumId', 'SKANDROID_ACTRL_Photo', 'albumPhotoList');
        $service->addRoute('skandroid.photo.upload', 'photo/upload', 'SKANDROID_ACTRL_Photo', 'upload');
        $service->addRoute('skandroid.photo.delete_photos', 'photo/delete-photos', 'SKANDROID_ACTRL_Photo', 'deletePhotos');

        /* ***************************** Kairat *************************** */

        // Matches
        $service->addRoute('skandroid.matches_get_list', 'matches/list', 'SKANDROID_ACTRL_Matches', 'getList');

        // Speedmatch
        $service->addRoute('skandroid.speedmatches_get_list', 'speedmatches/list', 'SKANDROID_ACTRL_Speedmatches', 'getList');
        $service->addRoute('skandroid.speedmatches_like_user', 'speedmatches/like', 'SKANDROID_ACTRL_Speedmatches', 'likeUser');
        $service->addRoute('skandroid.speedmatches_skip_user', 'speedmatches/skip', 'SKANDROID_ACTRL_Speedmatches', 'skipUser');

        // Sign Up
        $service->addRoute('skandroid.join_question_list', 'sign-up/questions', 'SKANDROID_ACTRL_SignUp', 'questionList');
        $service->addRoute('skandroid.validate_questions', 'questions/validate', 'SKANDROID_ACTRL_SignUp', 'validate');
        $service->addRoute('skandroid.join_upload_tmp_avatar', 'sign-up/upload-tmp-avatar', 'SKANDROID_ACTRL_SignUp', 'uploadAvatar');
        $service->addRoute('skandroid.join_user', 'sign-up/save', 'SKANDROID_ACTRL_SignUp', 'save');

        // Verify Email
        $service->addRoute("skandroid.verify_email", 'verify-email', "SKANDROID_ACTRL_SignUp", "verifyEmail");
        $service->addRoute("skandroid.resend_verification_email", 'resend-verification-email',"SKANDROID_ACTRL_SignUp", "resendVerificationEmail");

        // Facebook Connect
        $service->addRoute('skandroid.fbconnect_questions', 'fbconnect/questions', 'SKANDROID_ACTRL_FacebookSignUp', 'getFacebookLoginQuestion');
        $service->addRoute('skandroid.fbconnect_save', 'fbconnect/save', 'SKANDROID_ACTRL_FacebookSignUp', 'saveFacebookLogin');
        $service->addRoute('skandroid.try_login', 'fbconnect/try-login', 'SKANDROID_ACTRL_FacebookSignUp', 'tryLogIn');

        // Bookmarks
        $service->addRoute('skandroid.bookmarks.mark_user', 'bookmarks/mark', 'SKANDROID_ACTRL_Bookmarks', 'markUser');

        // Base
        $service->addRoute('skandroid.base.get_text', 'base/get-text', 'SKANDROID_ACTRL_Base', 'getText');
        $service->addRoute('skandroid.base.get_custom_page', 'base/get-custom-page', 'SKANDROID_ACTRL_Base', 'customPage');
        $service->addRoute('skandroid.user.mark_approval', 'user/mark-approval', 'SKANDROID_ACTRL_User', 'markApproval');
        $service->addRoute('skandroid.user.avatar_change', 'user/avatar-change', 'SKANDROID_ACTRL_User', 'avatarChange');

        // HotList
        $service->addRoute('skandroid.hotlist.count', 'hotlist/count', 'SKANDROID_ACTRL_HotList', 'getCount');
        $service->addRoute('skandroid.hotlist.user_list', 'hotlist/list', 'SKANDROID_ACTRL_HotList', 'getList');
        $service->addRoute('skandroid.hotlist.user_list.add', 'hotlist/list/add', 'SKANDROID_ACTRL_HotList', 'addToList');
        $service->addRoute('skandroid.hotlist.user_list.remove', 'hotlist/list/remove', 'SKANDROID_ACTRL_HotList', 'removeFromList');

        // Mailbox
        $service->addRoute('skandroid.mailbox.get_modes', 'mailbox/mode/get', 'SKANDROID_ACTRL_Mailbox', 'getModes');
        $service->addRoute('skandroid.mailbox.conversation_list', 'mailbox/conversation/list/:offset', 'SKANDROID_ACTRL_Mailbox', 'getConversationList');
        $service->addRoute('skandroid.mailbox.as_read', 'mailbox/conversation/as-read', 'SKANDROID_ACTRL_Mailbox', 'markAsRead');
        $service->addRoute('skandroid.mailbox.un_read', 'mailbox/conversation/un-read', 'SKANDROID_ACTRL_Mailbox', 'markUnRead');
        $service->addRoute('skandroid.mailbox.delete', 'mailbox/conversation/delete', 'SKANDROID_ACTRL_Mailbox', 'deleteConversation');
        $service->addRoute('skandroid.mailbox.get_messages', 'mailbox/messages', 'SKANDROID_ACTRL_Mailbox', 'getConversationMessages');
        $service->addRoute('skandroid.mailbox.get_history', 'mailbox/messages/history', 'SKANDROID_ACTRL_Mailbox', 'getConversationHistory');
        $service->addRoute('skandroid.mailbox.post_message', 'mailbox/message/send', 'SKANDROID_ACTRL_Mailbox', 'sendMessage');
        $service->addRoute('skandroid.mailbox.upload_attachment', 'mailbox/message/send-attachment', 'SKANDROID_ACTRL_Mailbox', 'uploadAttachment');
        $service->addRoute('skandroid.mailbox.attach_attachment', 'mailbox/compose/attach-attachment', 'SKANDROID_ACTRL_Mailbox', 'attachAttachment');
        $service->addRoute('skandroid.mailbox.delete_attachment', 'mailbox/compose/delete-attachment', 'SKANDROID_ACTRL_Mailbox', 'deleteAttachment');
        $service->addRoute('skandroid.mailbox.find_user', 'mailbox/compose/find-user', 'SKANDROID_ACTRL_Mailbox', 'findUser');
        $service->addRoute('skandroid.mailbox.compose_send', 'mailbox/compose/send', 'SKANDROID_ACTRL_Mailbox', 'createConversation');
        $service->addRoute('skandroid.mailbox.reply_send', 'mailbox/reply/send', 'SKANDROID_ACTRL_Mailbox', 'postReplyMessage');
        $service->addRoute('skandroid.mailbox.recipient_info', 'mailbox/recipient/info', 'SKANDROID_ACTRL_Mailbox', 'getRecipientInfo');
        $service->addRoute('skandroid.mailbox.chat_recipient_info', 'mailbox/chat-recipient/info', 'SKANDROID_ACTRL_Mailbox', 'getChatRecipientInfo');
        $service->addRoute('skandroid.mailbox.authorize_info', 'mailbox/authorize/info', 'SKANDROID_ACTRL_Mailbox', 'getAuthorizeInfo');
        $service->addRoute('skandroid.mailbox.authorize', 'mailbox/authorize', 'SKANDROID_ACTRL_Mailbox', 'authorize');
        $service->addRoute('skandroid.mailbox.wink_back', 'mailbox/wink-back', 'SKANDROID_ACTRL_Mailbox', 'winkBack');

/* ******************************* End **************************** */

$service->addRoute('skandroid.user.authenticate', 'user/authenticate', 'SKANDROID_ACTRL_User', 'authenticate');
$service->addRoute('skandroid.base.check_api', 'base/check-api', 'SKANDROID_ACTRL_Base', 'checkApi');
$service->addRoute('skandroid.block_user', 'user/block', 'SKANDROID_ACTRL_User', 'blockUser');
$service->addRoute('skandroid.user.signout', 'user/signout', 'SKANDROID_ACTRL_User', 'signout');
$service->addRoute('skandroid.add_device_id', 'user/add-device-id', 'SKANDROID_ACTRL_User', 'addDeviceId');

// Winks
$service->addRoute('skandroid.winks.send_wink', 'winks/send-wink', 'SKANDROID_ACTRL_Winks', 'sendWink');
$service->addRoute('skandroid.winks.accept_wink', 'winks/accept-wink', 'SKANDROID_ACTRL_Winks', 'acceptWink');
$service->addRoute('skandroid.winks.ignore_wink', 'winks/ignore-wink', 'SKANDROID_ACTRL_Winks', 'ignoreWink');
$service->addRoute('skandroid.winks.get_wink_requests', 'winks/get-wink-requests', 'SKANDROID_ACTRL_Winks', 'getWinkRequests');

OW::getRouter()->addRoute(new OW_Route('base_edit_user_datails', 'profile/:userId/edit/', 'BASE_CTRL_Edit', 'index'));

// Exceptions
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_User", "authenticate");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_Base", "checkApi");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_Base", "siteInfo");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_approved", "SKANDROID_ACTRL_Base", "siteInfo");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_verified", "SKANDROID_ACTRL_Base", "siteInfo");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.suspended", "SKANDROID_ACTRL_Base", "siteInfo");

OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.suspended", "SKANDROID_ACTRL_User", "signout");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_approved", "SKANDROID_ACTRL_User", "signout");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_User", "signout");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_verified", "SKANDROID_ACTRL_User", "signout");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_Base", "siteInfo");

OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.suspended", "SKANDROID_ACTRL_Ping", "ping");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_approved", "SKANDROID_ACTRL_Ping", "ping");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_Ping", "ping");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_verified", "SKANDROID_ACTRL_Ping", "ping");

OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_FacebookSignUp", "tryLogIn");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_FacebookSignUp", "getFacebookLoginQuestion");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_FacebookSignUp", "saveFacebookLogin");

OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_Base", "getText");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_Base", "customPage");

OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_SignUp", "questionList");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_SignUp", "validate");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_SignUp", "uploadAvatar");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_authenticated", "SKANDROID_ACTRL_SignUp", "save");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_verified", "SKANDROID_ACTRL_SignUp", "validate");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_verified", "SKANDROID_ACTRL_SignUp", "verifyEmail");
OW::getRequestHandler()->addCatchAllRequestsExclude("skandroid.not_verified", "SKANDROID_ACTRL_SignUp", "resendVerificationEmail");

/* *************************************************************** */

$handler = new SKANDROID_ACLASS_EventHandler();
$handler->init();

/*
$service->addRoute('skandroid.get_custom_page', 'site/getCustomPage', 'SKANDROID_ACTRL_Base', 'customPage'));

$service->addRoute('skandroid.user.current.getInfo', 'user/me/getInfo', 'SKANDROID_ACTRL_User', 'getInfo'));

$service->addRoute('skandroid.user.test', 'user/test', 'SKANDROID_ACTRL_User', 'test'));
$service->addRoute('skandroid.report', 'user/sendReport', 'SKANDROID_ACTRL_User', 'sendReport'));


$service->addRoute('skandroid.user_set_location', 'user/setLocation', 'SKANDROID_ACTRL_User', 'setLocation'));
$service->addRoute('skandroid.user_get_search_questions', 'user/getSearchQuestions', 'SKANDROID_ACTRL_User', 'getSearchQuestions'));
$service->addRoute('skandroid.user_save_question', 'user/saveQuestion', 'SKANDROID_ACTRL_User', 'saveQuestion'));
$service->addRoute('skandroid.avatar_change', 'user/avatarChange', 'SKANDROID_ACTRL_User', 'avatarChange'));
$service->addRoute('skandroid.avatar_from_poto', 'user/avatarFromPhoto', 'SKANDROID_ACTRL_User', 'avatarFromPhoto'));

// Hot List

$service->addRoute('hotlist.user_list.add', 'hotlist/userList/add', 'SKANDROID_ACTRL_HotList', 'addToList'));
$service->addRoute('hotlist.user_list.remove', 'hotlist/userList/remove', 'SKANDROID_ACTRL_HotList', 'removeFromList'));

// Photo






// Matches
$service->addRoute('matches.user_list', 'matches/userList', 'SKANDROID_ACTRL_Matches', 'getList'));

// SpeedMatch
$service->addRoute('speedmatch.get_user', 'speedmatch/getUser', 'SKANDROID_ACTRL_Speedmatch', 'getUser'));
$service->addRoute('speedmatch.get_criteria', 'speedmatch/getCriteria', 'SKANDROID_ACTRL_Speedmatch', 'getCriteria'));
$service->addRoute('speedmatch.like_user', 'speedmatch/likeUser', 'SKANDROID_ACTRL_Speedmatch', 'likeUser'));
$service->addRoute('speedmatch.skip_user', 'speedmatch/skipUser', 'SKANDROID_ACTRL_Speedmatch', 'skipUser'));

// Bookmarks
$service->addRoute('bookmarks.mark_user', 'bookmarks/markUser', 'SKANDROID_ACTRL_Bookmarks', 'markUser'));
$service->addRoute('bookmarks.user_list', 'bookmarks/userList', 'SKANDROID_ACTRL_Bookmarks', 'getList'));

// Winks
$service->addRoute('winks.send_wink_back', 'winks/sendWinkBack', 'SKANDROID_ACTRL_Winks', 'sendWinkBack'));
$service->addRoute('winks.accept_wink', 'winks/acceptWink', 'SKANDROID_ACTRL_Winks', 'acceptWink'));



// Mailbox
$service->addRoute('mailbox.get_unread_message_count', 'mailbox/getUnreadMessageCount', 'SKANDROID_ACTRL_Mailbox', 'getUnreadMessageCount'));
$service->addRoute('mailbox.user_list', 'mailbox/userList', 'SKANDROID_ACTRL_Mailbox', 'getList'));
$service->addRoute('mailbox.post_message', 'mailbox/postMessage', 'SKANDROID_ACTRL_Mailbox', 'postMessage'));
$service->addRoute('mailbox.get_new_messages', 'mailbox/getNewMessages', 'SKANDROID_ACTRL_Mailbox', 'getNewMessages'));
$service->addRoute('mailbox.get_messages', 'mailbox/getMessages', 'SKANDROID_ACTRL_Mailbox', 'getMessages'));
$service->addRoute('mailbox.get_history', 'mailbox/getHistory', 'SKANDROID_ACTRL_Mailbox', 'getHistory'));
$service->addRoute('mailbox.upload_attachment', 'mailbox/uploadAttachment', 'SKANDROID_ACTRL_Mailbox', 'uploadAttachment'));
$service->addRoute('mailbox.authorize', 'mailbox/authorize', 'SKANDROID_ACTRL_Mailbox', 'authorize'));



// Sign Up
$service->addRoute('sign_up.question_list', 'signUp/questionList', 'SKANDROID_ACTRL_SignUp', 'questionList'));
$service->addRoute('sign_up.save', 'signUp/save', 'SKANDROID_ACTRL_SignUp', 'save'));

$service->addRoute('sign_up.try_log_in', 'signUp/tryLogIn', 'SKANDROID_ACTRL_SignUp', 'tryLogIn'));

// Ping
$service->addRoute('base.ping', 'base/Ping', 'SKANDROID_ACTRL_Ping', 'ping'));








*/

    }
}
