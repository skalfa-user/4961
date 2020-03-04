<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
BOL_BillingService::getInstance()->deactivateProduct('membership_plan');

OW::getNavigation()->deleteMenuItem('membership', 'subscribe_page_heading');
OW::getNavigation()->deleteMenuItem('membership', 'subscribe_page_heading_mobile');

BOL_ComponentAdminService::getInstance()->deleteWidget('MEMBERSHIP_CMP_MyMembershipWidget');
BOL_ComponentAdminService::getInstance()->deleteWidget('MEMBERSHIP_CMP_UserMembershipWidget');
BOL_ComponentAdminService::getInstance()->deleteWidget('MEMBERSHIP_CMP_PromoWidget');