<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * User credits product adapter class.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.user_credits.classes
 * @since 1.0
 */
class USERCREDITS_CLASS_UserCreditsPackProductAdapter implements OW_BillingProductAdapter
{
    const PRODUCT_KEY = 'user_credits_pack';

    const RETURN_ROUTE = 'usercredits.buy_credits';

    public function getProductKey()
    {
        return self::PRODUCT_KEY;
    }

    public function getProductOrderUrl()
    {
        return OW::getRouter()->urlForRoute(self::RETURN_ROUTE);
    }

    public function deliverSale( BOL_BillingSale $sale )
    {
        $packId = $sale->entityId;
        
        $creditsService = USERCREDITS_BOL_CreditsService::getInstance();
        
        $pack = $creditsService->findPackById($packId);
        
        if ( !$pack )
        {
            return false;
        }
        
        if ( $creditsService->increaseBalance($sale->userId, $pack->credits) )
        {
            $creditsService->sendPackPurchasedNotification($sale->userId, $pack->credits, $sale->totalAmount);
            
            $actionDto = USERCREDITS_BOL_CreditsService::getInstance()->findAction('usercredits', 'buy_credits');
        
            if ( !empty($actionDto) && !empty($actionDto->id) )
            {
                $creditsService->logAction($actionDto->id, $sale->userId, $pack->credits);
            }
            return true;
        }
        
        return false;
    }
}