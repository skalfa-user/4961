<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
OW::getRouter()->addRoute(new OW_Route('billing_ccbill_select_type', 'billing-ccbill/select', 'BILLINGCCBILL_CTRL_Order', 'select'));
OW::getRouter()->addRoute(new OW_Route('billing_ccbill_order_form', 'billing-ccbill/order/:type', 'BILLINGCCBILL_CTRL_Order', 'form'));
OW::getRouter()->addRoute(new OW_Route('billing_ccbill_admin', 'admin/billing-ccbill', 'BILLINGCCBILL_CTRL_Admin', 'index'));

BILLINGCCBILL_CLASS_EventHandler::getInstance()->init();