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

use \Firebase\JWT\JWT;

/**
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.skandroid.api.controllers
 * @since 1.0
 */
class SKANDROID_ACTRL_Billing extends OW_ApiActionController
{
    public function getSubscribeData()
    {
        if ( !SKANDROID_ABOL_Service::getInstance()->isBillingEnabled() )
        {
            throw new ApiResponseErrorException();
        }
        
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        $pm = OW::getPluginManager();
        $authService = BOL_AuthorizationService::getInstance();

        $membershipActive = $pm->isPluginActive('membership');
        $creditsActive = $pm->isPluginActive('usercredits');

        $this->assign('membershipActive', $membershipActive);
        $this->assign('creditsActive', $creditsActive);

        // get user account type
        $accTypeName = OW::getUser()->getUserObject()->getAccountType();
        $accType = BOL_QuestionService::getInstance()->findAccountTypeByName($accTypeName);

        if ( $membershipActive )
        {
            $msService = MEMBERSHIP_BOL_MembershipService::getInstance();

            $benefits = $this->getBenefits();

            /* @var $defaultRole BOL_AuthorizationRole */
            $defaultRole = $authService->getDefaultRole();

            // get user current membership
            $userMembership = $msService->getUserMembership($userId);
            $userRoleIds = array( $defaultRole->id );

            $current = null;
            if ( $userMembership )
            {
                $type = $msService->findTypeById($userMembership->typeId);
                if ( $type )
                {
                    
                    $userRoleIds[] = $type->roleId;
                }

                $current = $type->id;
            }
            if ( !$current )
            {
                $current = "-1";
            }
            $this->assign('currentType', $current);

            // get memberships
            $typeList = $msService->getTypeList($accType->id);

            $exclude = $msService->getUserTrialPlansUsage($userId);
            $plans = $msService->getTypePlanList($exclude);

            // prepend default role
            $default = array(
                'id' => "-1",
                'roleId' => $defaultRole->id,
                'label' => $msService->getMembershipTitle($defaultRole->id),
                'plans' => null,
                'benefits' => isset($benefits[$defaultRole->id]) ? array_values($benefits[$defaultRole->id])  : null
            );
            $types = array( $default );

            if ( $typeList )
            {
                foreach ( $typeList as $type )
                {
                    $types[] = array(
                        'id' => $type->id,
                        'roleId' => $type->roleId,
                        'label' => $msService->getMembershipTitle($type->roleId),
                        'plans' => isset($plans[$type->id]) ? $this->formatPlans($plans[$type->id]) : null,
                        'benefits' => isset($benefits[$type->roleId]) ? array_values($benefits[$type->roleId])  : null
                    );
                }
            }

            $this->assign('types', $types);
        }

        if ( $creditsActive )
        {
            $creditsService = USERCREDITS_BOL_CreditsService::getInstance();

            $balance = $creditsService->getCreditsBalance($userId);

            $this->assign('balance', $balance);

            $packs = $creditsService->getPackList($accType->id);

            if ( $packs )
            {
                foreach ( $packs as &$pack )
                {
                    $pack['title'] = $this->getPackageTitle($pack['credits'], $pack['price']);
                }
            }
            $this->assign('packs', $packs);

            $losing = $this->formatActions($creditsService->findCreditsActions('lose', $accType->id, false));
            $this->assign('spendingActions', $losing);

            $earning = $this->formatActions($creditsService->findCreditsActions('earn', $accType->id, false));
            $this->assign('earningActions', $earning);
        }
    }

    public function suggestPaymentOptions($params)
    {
        if ( !SKANDROID_ABOL_Service::getInstance()->isBillingEnabled() )
        {
            throw new ApiResponseErrorException();
        }
        
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['pluginKey']) )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['actionKey']) )
        {
            throw new ApiResponseErrorException();
        }

        $pluginKey = $params['pluginKey'];
        $actionKey = $params['actionKey'];
        
        $authService = BOL_AuthorizationService::getInstance();

        $pm = OW::getPluginManager();
        $membershipActive = $pm->isPluginActive('membership');
        $creditsActive = $pm->isPluginActive('usercredits');

        if ( $membershipActive )
        {
            $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
            // get user current membership
            $userMembership = $membershipService->getUserMembership($userId);

            if ( $userMembership )
            {
                $type = $membershipService->findTypeById($userMembership->typeId);
                $roleId = $type->roleId;
            }
            else
            {
                /* @var $defaultRole BOL_AuthorizationRole */
                $defaultRole = $authService->getDefaultRole();
                $roleId = $defaultRole->id;
            }

            $this->assign('current', $membershipService->getMembershipTitle($roleId));
            
            $suggestedPlan = $this->getSuggestedMembershipPlan($userId, $pluginKey, $actionKey);
            $this->assign('plan', $suggestedPlan);
            
            if ( !$suggestedPlan )
            {
                //$membershipActive = false;
                $suggestedType = null;
            }
            else
            {
                $typeByPlan = $membershipService->findTypeByPlanId($suggestedPlan['id']);
                $suggestedType = $typeByPlan ? array( 'id' => $typeByPlan->id, 'label' => $membershipService->getMembershipTitle($typeByPlan->roleId) ) : null;
            }

            $this->assign('type', $suggestedType);
        }
        
        if ( $creditsActive )
        {
            $creditsService = USERCREDITS_BOL_CreditsService::getInstance();
            $balance = $creditsService->getCreditsBalance($userId);
            $this->assign('balance', $balance);

            $suggestedPack = $this->getSuggestedCreditsPack($userId, $pluginKey, $actionKey);

            if ( !empty($suggestedPack) && !empty($suggestedPack['title']) )
            {
                $suggestedPack['title'] = strip_tags($suggestedPack['title']);
            }

            $this->assign('pack', empty($suggestedPack) ? null : $suggestedPack);
        }

        $this->assign('membershipActive', $membershipActive);
        $this->assign('creditsActive', $creditsActive);
    }

    private function verifyMarketInApp($signed_data, $signature, $public_key_base64)
    {
        $key = "-----BEGIN PUBLIC KEY-----\n" .
                chunk_split($public_key_base64, 64, "\n") .
                '-----END PUBLIC KEY-----';
        //using PHP to create an RSA key
        $key = openssl_get_publickey($key);
        //$signature should be in binary format, but it comes as BASE64.
        //So, I'll convert it.
        $signature = base64_decode($signature);
        //using PHP's native support to verify the signature
        $result = @openssl_verify(
                $signed_data, $signature, $key, OPENSSL_ALGO_SHA1);

        if ( 0 === $result )
        {
            return false;
        }
        else if ( 1 !== $result )
        {
            return false;
        }
        else
        {
            return true;
        }
    }
    
    public function setTrialPlan($params)
    {
        if ( !SKANDROID_ABOL_Service::getInstance()->isBillingEnabled() )
        {
            throw new ApiResponseErrorException();
        }
        
        if ( !OW::getPluginManager()->isPluginActive("membership") )
        {
            throw new ApiResponseErrorException();
        }
        
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
        if ( empty($params['plan']) || !$plan = $membershipService->findPlanById($params['plan']) )
        {
            throw new ApiResponseErrorException();
        }
        
        $this->assign('registered', false);
        
        if ( $plan->price == 0 ) // trial plan
        {
            $userId = OW::getUser()->getId();
            
            // check if trial plan used
            $used = $membershipService->isTrialUsedByUser($userId);
            
            if ( $used )
            {
                $this->assign('error', 'trial_used_error');//OW::getLanguage()->text('membership', 'trial_used_error'));
            }
            else // give trial plan
            {
                $userMembership = new MEMBERSHIP_BOL_MembershipUser();

                $userMembership->userId = $userId;
                $userMembership->typeId = $plan->typeId;
                $userMembership->expirationStamp = time() + (int) $plan->period * $membershipService->getInstance()->getPeriodUnitFactor($plan->periodUnits);
                $userMembership->recurring = 0;
                $userMembership->trial = 1;

                $membershipService->setUserMembership($userMembership);
                $membershipService->addTrialPlanUsage($userId, $plan->id, $plan->period, $plan->periodUnits);

                //OW::getFeedback()->info($lang->text('membership', 'trial_granted', array('days' => $plan->period)));
                $this->assign('registered', true);
            }
        }
    }
        
    public function preverifySale($params)
    {
        if ( empty($params['purchase']) )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['signature']) )
        {
            throw new ApiResponseErrorException();
        }
        
        if ( !SKANDROID_ABOL_Service::getInstance()->isBillingEnabled() )
        {
            throw new ApiResponseErrorException();
        }

        $logger = OW::getLogger('skandroid');
        $logger->addEntry(print_r($params, true), ' purchase data');
        
        $valid = $this->verifyMarketInApp($params['purchase'], $params['signature'], trim( OW::getConfig()->getValue('skandroid', 'public_key') )  );

        $purchase = json_decode($params['purchase'], true);

        if ( empty($purchase['developerPayload']) )
        {
            throw new ApiResponseErrorException();
        }

        $developerPayload = json_decode(base64_decode($purchase['developerPayload']), true);

        $userId = !empty($developerPayload['userId']) ? $developerPayload['userId'] : null;

        $purchaseProductId = !empty($purchase['productId']) ? $purchase['productId'] : null;
        $payloadProductId = !empty($developerPayload['productId']) ? $developerPayload['productId'] : null;

        $purchaseHash = $this->generateHash(OW::getUser()->getId(), $purchaseProductId);
        $payloadHash = !empty($developerPayload['hash']) ? $developerPayload['hash'] : null;


        $logger->addEntry(print_r($params['purchase'], true), 'purchase.validation');
        $logger->writeLog();

        $this->assign('error', null);

        if ( !isset($userId) || OW::getUser()->getId() != $userId )
        {
            $this->assign('isValid', false);
            $this->assign('error', 'Undefined user id');

            return;
        }

        if ( !isset($valid) )
        {
            $this->assign('isValid', false);
            $this->assign('error', 'Purchase validation failed');

            return;
        }

        if ( !isset($purchaseProductId) || !isset($payloadProductId) || $payloadProductId != $purchaseProductId )
        {
            $this->assign('isValid', false);
            $this->assign('error', 'Payload validation faild.  Invalid product Id');

            return;
        }

        if ( !isset($purchaseHash) || !isset($payloadHash) || $payloadHash != $purchaseHash )
        {
            $this->assign('isValid', false);
            $this->assign('error', 'Payload validation faild.');

            return;
        }

        $billingService = BOL_BillingService::getInstance();

        $service = SKANDROID_ABOL_Service::getInstance();

        $orderId = isset($purchase['orderId']) ? $purchase['orderId'] : null;
        $productId = isset($purchase['productId']) ? $purchase['productId'] : null;
        $purchaseTime = isset($purchase['purchaseTime']) ? $purchase['purchaseTime'] : null;
        $purchaseToken = isset($purchase['purchaseToken']) ? $purchase['purchaseToken'] : null;


        $configs = OW::getConfig()->getValues('skandroid');
        // verify sale in google play
        try {
            $inAppPurchaseValidator = new SKANDROID_ACLASS_InAppPurchaseValidator($configs['service_account_id'],
                $configs['service_account_private_key']);
            $isValid = $inAppPurchaseValidator->preValidatePurchase($purchaseToken, $purchaseTime, $purchase['developerPayload']);

            if ( !$isValid )
            {
                $this->assign('isValid', false);
                $this->assign('error', 'Payload validation failed.');
                return;
            }
        } catch (InvalidTokenException $ex) {
            $logger->addEntry($ex, "purchase.validation");

            $this->assign('isValid', false);
            $this->assign('error', 'Payload validation failed.');

            return;
        }
        catch (\DomainException $ex) {
            $logger->addEntry($ex, "purchase.validation");

            $this->assign('isValid', false);
            $this->assign('error', 'Payload validation faild.');

            return;
        }

        $sale = $billingService->getSaleByGatewayTransactionId(SKANDROID_ACLASS_InAppPurchaseAdapter::GATEWAY_KEY, md5($orderId));

        if ( $sale )
        { // sale already registered
            $this->assign('isValid', false);
            $this->assign('error', 'Sale already registered');
            return;
        }

        $product = $service->findProductByItunesProductId($productId);

        if ( !$product )
        {
            $this->assign('isValid', false);
            $this->assign('error', 'Product not found');
        }
        else
        {
            $this->assign('isValid', true);
        }
    }
        
    public function verifySale($params)
    {
        if ( !SKANDROID_ABOL_Service::getInstance()->isBillingEnabled() )
        {
            throw new ApiResponseErrorException();
        }
        
        if ( empty($params['purchase']) )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['signature']) )
        {
            throw new ApiResponseErrorException();
        }

        $logger = OW::getLogger('skandroid');
        $logger->addEntry(print_r($params, true), ' purchase data');

        $valid = $this->verifyMarketInApp($params['purchase'], $params['signature'], trim( OW::getConfig()->getValue('skandroid', 'public_key') )  );

        $purchase = json_decode($params['purchase'], true);

        if ( empty($purchase['developerPayload']) )
        {
            throw new ApiResponseErrorException();
        }

        $developerPayload = json_decode(base64_decode($purchase['developerPayload']), true);

        $userId = !empty($developerPayload['userId']) ? $developerPayload['userId'] : null;

        $purchaseProductId = !empty($purchase['productId']) ? $purchase['productId'] : null;
        $payloadProductId = !empty($developerPayload['productId']) ? $developerPayload['productId'] : null;

        $purchaseHash = $this->generateHash(OW::getUser()->getId(), $purchaseProductId);
        $payloadHash = !empty($developerPayload['hash']) ? $developerPayload['hash'] : null;


        $logger->addEntry(print_r($params['purchase'], true), 'purchase.validation');
        $logger->writeLog();

        $this->assign('error', null);

        if ( !isset($userId) || OW::getUser()->getId() != $userId )
        {
            $this->assign('registered', false);
            $this->assign('error', 'Undefined user id');

            return;
        }

        if ( !isset($valid) )
        {
            $this->assign('registered', false);
            $this->assign('error', 'Purchase validation failed');

            return;
        }

        if ( !isset($purchaseProductId) || !isset($payloadProductId) || $payloadProductId != $purchaseProductId )
        {
            $this->assign('registered', false);
            $this->assign('error', 'Payload validation failed.  Invalid product Id');

            return;
        }

        if ( !isset($purchaseHash) || !isset($payloadHash) || $payloadHash != $purchaseHash )
        {
            $this->assign('registered', false);
            $this->assign('error', 'Payload validation failed.');

            return;
        }

        $billingService = BOL_BillingService::getInstance();

        $service = SKANDROID_ABOL_Service::getInstance();

        $orderId = isset($purchase['orderId']) ? $purchase['orderId'] : null;
        $productId = isset($purchase['productId']) ? $purchase['productId'] : null;
        $purchaseTime = isset($purchase['purchaseTime']) ? $purchase['purchaseTime'] : null;
        $purchaseToken = isset($purchase['purchaseToken']) ? $purchase['purchaseToken'] : null;


        $configs = OW::getConfig()->getValues('skandroid');
        // verify sale in google play
        try {
            $inAppPurchaseValidator = new SKANDROID_ACLASS_InAppPurchaseValidator($configs['service_account_id'],
                $configs['service_account_private_key']);
            $isValid = $inAppPurchaseValidator->validatePurchase($purchaseToken, $purchaseTime, $purchase['developerPayload']);

            if ( !$isValid )
            {
                $this->assign('isValid', false);
                $this->assign('error', 'Payload validation failed.');
                return;
            }

        } catch (InvalidTokenException $ex) {
            $logger->addEntry($ex, "purchase.validation");

            $this->assign('isValid', false);
            $this->assign('error', 'Payload validation failed.');

            return;
        }

        $sale = $billingService->getSaleByGatewayTransactionId(SKANDROID_ACLASS_InAppPurchaseAdapter::GATEWAY_KEY, md5($orderId));

        if ( $sale )
        { // sale already registered
            $this->assign('registered', false);
            $this->assign('error', 'Sale already registered');
            return;
        }

        $product = $service->findProductByItunesProductId($productId);

        if ( !$product )
        {
            $this->assign('registered', false);
            $this->assign('error', 'Product not found');
        }
        else
        {
            // sale object
            $sale = new BOL_BillingSale();
            $sale->pluginKey = $product['pluginKey'];
            $sale->entityDescription = $product['entityDescription'];
            $sale->entityKey = $product['entityKey'];
            $sale->entityId = $product['entityId'];
            $sale->price = $product['price'];
            $sale->period = $product['period'];
            $sale->userId = $userId;
            $sale->recurring = $product['recurring'];
            $sale->periodUnits = $product['periodUnits'];

            $this->assign('product', array(
                'userId' => $userId,
                'username' => BOL_UserService::getInstance()->getUserName($userId),
                'pluginKey' => $product['pluginKey'],
                'membershipTitle' => $product['membershipTitle'],
                'entityDescription' => $product['entityDescription']
            ));

            $saleId = $billingService->initSale($sale, SKANDROID_ACLASS_InAppPurchaseAdapter::GATEWAY_KEY);
            $sale = $billingService->getSaleById($saleId);

            $sale->timeStamp = $purchaseTime / 1000;
            $sale->transactionUid = $orderId;
            $sale->extraData = json_encode(array( 'orderId' => $orderId, 'extra' => $purchase['developerPayload'] ));
            BOL_BillingSaleDao::getInstance()->save($sale);

            $productAdapter = null;
            switch ( $sale->pluginKey )
            {
                case 'membership':
                    $productAdapter = new MEMBERSHIP_CLASS_MembershipPlanProductAdapter();
                    break;

                case 'usercredits':
                    $productAdapter = new USERCREDITS_CLASS_UserCreditsPackProductAdapter();
                    break;
            }

            $billingService->deliverSale($productAdapter, $sale);

            $this->assign('registered', true);
        }

        return;
    }

    // Utils
    private function generateHash($userId, $productId)
    {
        return base64_encode($userId . $productId);
    }

    private function formatPlans(array $plans)
    {
        $result = array();
        $used = MEMBERSHIP_BOL_MembershipService::getInstance()->isTrialUsedByUser(OW::getUser()->getId());
        
        foreach ( $plans as $plan )
        {
            if( $used && $plan['dto']->price == 0)
            {
                continue;
            }

            $result[] = array(
                'id' => $plan['dto']->id,
                'price' => $plan['dto']->price,
                'period' => $plan['dto']->period,
                'recurring' => $plan['dto']->recurring,
                'label' => $plan['plan_format'],
                'productId' => $plan['productId']
            );
        }

        return $result;
    }

    private function formatActions(array $actions)
    {
        if ( !$actions )
        {
            return array();
        }

        $result = array();
        foreach ( $actions as $action )
        {
            $result[] = array(
                'id' => $action['id'],
                'label' => $action['title'],
                'amount' => isset($action['settingsRoute']->settingsRoute) ? null : $action['amount']
            );
        }

        return $result;
    }

    private function getBenefits()
    {
        $authService = BOL_AuthorizationService::getInstance();
        $permissionList = $authService->getPermissionList();

        foreach ( $permissionList as $permission )
        {
            /* @var $permission BOL_AuthorizationPermission */
            $permissions[$permission->roleId][$permission->actionId] = true;
        }

        $roleList = $authService->getRoleList();
        $groupList = SKANDROID_ABOL_Service::getInstance()->getAuthorizationActions();
        $pluginList = SKANDROID_ABOL_Service::getInstance()->getAndroidAvailablePluginList();

        foreach ( $groupList as $key => $group )
        {
            if( count($group['actions']) === 0 || !OW::getPluginManager()->isPluginActive($group['name']) || !empty($pluginList) && !in_array($group['name'], $pluginList) )
            {
                unset($groupList[$key]);
            }
        }

        $result = array();
        /* @var $role BOL_AuthorizationRole */
        foreach ( $roleList as $role )
        {
            $tempGroupList = $groupList;
            /* @var $group BOL_AuthorizationGroup */
            foreach ( $tempGroupList as &$group )
            {
                foreach ( $group['actions'] as &$action )
                {
                    $action['allowed'] = isset($permissions[$role->id][$action['id']]);
                }
            }

            $result[$role->id] = $tempGroupList;
        }

        return $result;
    }

    private function getSuggestedCreditsPack($userId, $pluginKey, $actionKey)
    {
        $creditsService = USERCREDITS_BOL_CreditsService::getInstance();

        $params = array();

        $params['groupName'] = $pluginKey;
        $params['actionName'] = $actionKey;
        $params['userId'] = $userId;
        
        $actionEvent = new OW_Event('usercredits.get_action_key', $params);
        OW::getEventManager()->trigger($actionEvent);
        $data = $actionEvent->getData();
        
        $actionName = !empty($data) ? $data : $actionKey;
        $action = $creditsService->findAction($pluginKey, $actionName);
        
        if ( !$action )
        {
            return null;
        }

        // get user account type
        $accTypeName = BOL_UserService::getInstance()->findUserById($userId)->getAccountType();
        $accType = BOL_QuestionService::getInstance()->findAccountTypeByName($accTypeName);
        
        $packs = $creditsService->getPackList($accType->id);
        
        if ( !$packs )
        {
            return null;
        }

        $actionPrice = $creditsService->findActionPrice($action->id, $accType->id);
        
        if ( !$actionPrice )
        {
            return null;
        }

        $balance = $creditsService->getCreditsBalance($userId);

        $suggestedPack = array();
        
        foreach ( $packs as $pack )
        {
            if ( ($pack['price'] + $balance >= $actionPrice->amount) && !$actionPrice->disabled )
            {
                $suggestedPack = $pack;
                break;
            }
        }

        return $suggestedPack;
    }

    private function getSuggestedMembershipPlan($userId, $pluginKey, $actionKey)
    {
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
        $authService = BOL_AuthorizationService::getInstance();

        $action = $authService->findAction($pluginKey, $actionKey);

        if ( !$action )
        {
            return null;
        }

        if ( OW::getAuthorization()->isUserAuthorized($userId, $pluginKey, $actionKey) )
        {
            return null;
        }

        // get user account type
        $accTypeName = BOL_UserService::getInstance()->findUserById($userId)->getAccountType();
        $accType = BOL_QuestionService::getInstance()->findAccountTypeByName($accTypeName);
        $typeList = $membershipService->getTypeList($accType->id);
        /*@var $membership MEMBERSHIP_BOL_MembershipUser */
        $membership = $membershipService->getUserMembership($userId);

        $exclude = $membershipService->getUserTrialPlansUsage($userId);
        $plans = $membershipService->getTypePlanList($exclude);

        $permissions = $authService->getPermissionList();

        $suggestedPlanId = null;
        $suggestedPlanPrice = PHP_INT_MAX;
        $suggestedPlanTitle = null;
        $suggestedPlanPeriod = null;

        if ( !$typeList )
        {
            return null;
        }

        foreach ( $typeList as $type )
        {
            if ( !isset($plans[$type->id]) )
            {
                continue;
            }

            if ( !$this->actionPermittedForMembershipType($action, $type, $permissions) )
            {
                continue;
            }
            
            if ( !empty($membership) && ( $membership->typeId == $type->id ) )
            {
                continue;
            }
            
            $used = $membershipService->isTrialUsedByUser($userId);
            
            foreach ( $plans[$type->id] as $plan )
            {            
                if( $used && $plan['dto']->price == 0)
                {
                    continue;
                }
                
                /*@var $plan['dto'] MEMBERSHIP_BOL_MembershipPlan*/
                if ( $plan['dto']->price < $suggestedPlanPrice )
                {
                    $suggestedPlanId = $plan['dto']->id;
                    $suggestedPlanPrice = $plan['dto']->price;
                    $suggestedPlanTitle = $plan['plan_format'];
                    $suggestedPlanPrice = $plan['dto']->price;
                    $suggestedPlanPeriod = $plan['dto']->period;
                }
            }
        }

        if ( $suggestedPlanId )
        {
            return array( 'id' => $suggestedPlanId, 'title' => $suggestedPlanTitle,
                'productId' => $membershipService->getPlanProductId($suggestedPlanId),
                'price' => $suggestedPlanPrice, 'period' => $suggestedPlanPeriod );
        }

        return null;
    }

    private function actionPermittedForMembershipType($action, MEMBERSHIP_BOL_MembershipType $type, $permissions)
    {
        foreach ( $permissions as $permission )
        {
            if ( $type->roleId == $permission->roleId && $action->id == $permission->actionId )
            {
                return true;
            }
        }

        return false;
    }

    private function getPackageTitle($credits, $price)
    {
        $currency = BOL_BillingService::getInstance()->getActiveCurrency();

        return $credits . ' Credits for ' . $currency . ' ' . floatval($price);
    }

}
