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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_system_plugins.skadateios.api.controllers
 * @since 1.0
 */
class SKADATEIOS_ACTRL_Billing extends OW_ApiActionController
{
    public function getSubscribeData()
    {
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
            $userRoleIds = array($defaultRole->id);

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
                'benefits' => isset($benefits[$defaultRole->id]) ? $benefits[$defaultRole->id] : null
            );
            $types = array($default);

            if ( $typeList )
            {
                foreach ( $typeList as $type )
                {
                    $types[] = array(
                        'id' => $type->id,
                        'roleId' => $type->roleId,
                        'label' => $msService->getMembershipTitle($type->roleId),
                        'plans' => isset($plans[$type->id]) ? $this->formatPlans($plans[$type->id]) : null,
                        'benefits' => isset($benefits[$type->roleId]) ? $benefits[$type->roleId] : null
                    );
                }
            }

            $this->assign('types', $types);
        }

        if ( $creditsActive )
        {
            $creditsService = USERCREDITS_BOL_CreditsService::getInstance();

            $balance = $creditsService->getCreditsBalance($userId);

            $this->assign('balance', (string) $balance);

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

    public function suggestPaymentOptions( $params )
    {
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

        $current = null;
        $pack = null;
        $plan = null;
        $balance = null;
        
        if ( $membershipActive )
        {
            $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
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
            
            $current = $membershipService->getMembershipTitle($roleId);
            $plan = $this->getSuggestedMembershipPlan($userId, $pluginKey, $actionKey);
        }
        
        if ( $creditsActive )
        {
            $balance = OW::getEventManager()->call("usercredits.get_balance", array(
                "userId" => $userId
            ));
            
            $pack = $this->getSuggestedCreditsPack($userId, $pluginKey, $actionKey);
        }
        
        $this->assign('current', $current);
        $this->assign('balance', $balance);
        $this->assign('pack', $pack);

        $this->assign('plan', $plan);
        if ( !$plan )
        {
            //$membershipActive = false;
            $suggestedType = null;
        }
        else
        {
            $typeByPlan = $membershipService->findTypeByPlanId($plan['id']);
            $suggestedType = $typeByPlan ? array('label' => $membershipService->getMembershipTitle($typeByPlan->roleId)) : null;
        }
        $this->assign('type', $suggestedType);

        $this->assign('membershipActive', $membershipActive);
        $this->assign('creditsActive', $creditsActive);
    }

    public function verifySale( $params )
    {
        if ( empty($params['receipt']) )
        {
            throw new ApiResponseErrorException();
        }

        $userId = !empty($params['userId']) ? $params['userId'] : null;

        $receipt = trim($params['receipt']);

        $logger = OW::getLogger('skadateios');
        $logger->addEntry(print_r($params, true), 'receipt.data');

        $configs = OW::getConfig()->getValues("skadateios");
        $validator = new SKADATEIOS_ACLASS_ItunesReceiptValidator($configs["itunes_mode"], $configs["itunes_secret"]);

        $data = $validator->validateReceipt($receipt);

        $logger->addEntry(print_r($data, true), 'receipt.validation');
        $logger->writeLog();

        if ( !isset($data['status']) )
        {
            $this->assign('registered', false);
            $this->assign('error', 'Receipt validation failed');

            return;
        }

        if ( $data['status'] == 0 ) // ok
        {
            $environment = $data['environment'];
            $bundleId = $data['receipt']['bundle_id'];
            $inAppData = $data['receipt']['in_app'];

            foreach ( $inAppData as $inApp )
            {
                $productId = $inApp['product_id'];
                $transactionId = $inApp['transaction_id'];

                $billingService = BOL_BillingService::getInstance();
                $service = SKADATEIOS_ABOL_Service::getInstance();

                $sale = $billingService->getSaleByGatewayTransactionId(
                    SKADATEIOS_ACLASS_InAppPurchaseAdapter::GATEWAY_KEY,
                    $transactionId
                );

                if ( $sale ) // sale already registered
                {
                    continue;
                }

                $originalTransactionId = isset($inApp['original_transaction_id']) ? $inApp['original_transaction_id'] : null;

                if ( $originalTransactionId )
                {
                    $originalSale = $billingService->getSaleByGatewayTransactionId(
                        SKADATEIOS_ACLASS_InAppPurchaseAdapter::GATEWAY_KEY,
                        $originalTransactionId
                    );

                    if ( $originalSale && !$userId )
                    {
                        $userId = $originalSale->userId;
                    }
                }

                $purchaseTime = $inApp['purchase_date_ms'] / 1000;

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

                    $saleId = $billingService->initSale($sale, SKADATEIOS_ACLASS_InAppPurchaseAdapter::GATEWAY_KEY);
                    $sale = $billingService->getSaleById($saleId);

                    $sale->timeStamp = $purchaseTime;
                    $sale->transactionUid = $transactionId;
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
                    
                    $this->assign("pluginKey", $sale->pluginKey);
                    $this->assign("userId", $sale->userId);
                    $this->assign("userName", BOL_UserService::getInstance()->getUserName($sale->userId));
                    $this->assign("description", $sale->entityDescription);
                    $this->assign("membershipTitle", empty($product["membershipTitle"]) ? null : $product["membershipTitle"]);
                }

                return;
            }
        }

        $this->assign('registered', false);
        $this->assign('error', 'Receipt validation failed');
    }

    // Utils

    private function formatPlans( array $plans )
    {
        $result = array();

        foreach ( $plans as $plan )
        {
            /**
             * @var $planDto MEMBERSHIP_BOL_MembershipPlan
             */
            $planDto = $plan['dto'];

            $result[] = array(
                'id' => $planDto->id,
                'price' => $planDto->price,
                'period' => $planDto->period,
                'periodUnits' => $planDto->periodUnits,
                'recurring' => $planDto->recurring,
                'label' => $plan['plan_format'],
                'productId' => $plan['productId']
            );
        }

        return $result;
    }

    private function formatActions( array $actions )
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
        $groupList = SKADATEIOS_ABOL_Service::getInstance()->getAuthorizationActions();

        $result = array();
        foreach ( $roleList as $role )
        {
            foreach ( $groupList as &$group )
            {
                foreach ( $group['actions'] as &$action )
                {
                    $action['allowed'] = isset($permissions[$role->id][$action['id']]);
                }
            }

            $result[$role->id] = $groupList;
        }

        return $result;
    }

    private function getSuggestedCreditsPack( $userId, $pluginKey, $actionKey )
    {
        $creditsService = USERCREDITS_BOL_CreditsService::getInstance();

        $action = $creditsService->findAction($pluginKey, $actionKey);

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

        $suggestedPack = null;
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

    private function getSuggestedMembershipPlan( $userId, $pluginKey, $actionKey )
    {
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
        $authService = BOL_AuthorizationService::getInstance();

        $action = $authService->findAction($pluginKey, $actionKey);

        if ( !$action )
        {
            return null;
        }

        // get user account type
        $accTypeName = BOL_UserService::getInstance()->findUserById($userId)->getAccountType();
        $accType = BOL_QuestionService::getInstance()->findAccountTypeByName($accTypeName);
        $typeList = $membershipService->getTypeList($accType->id);

        $exclude = $membershipService->getUserTrialPlansUsage($userId);
        $plans = $membershipService->getTypePlanList($exclude);

        $permissions = $authService->getPermissionList();

        $suggestedPlanId = null;
        $suggestedPlanPrice = PHP_INT_MAX;
        $suggestedPlanTitle = null;

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

            foreach ( $plans[$type->id] as $plan )
            {
                if ( $plan['dto']->price < $suggestedPlanPrice )
                {
                    $suggestedPlanId = $plan['dto']->id;
                    $suggestedPlanPrice = $plan['dto']->price;
                    $suggestedPlanTitle = $plan['plan_format'];
                }
            }
        }

        if ( $suggestedPlanId )
        {
            return array('id' => $suggestedPlanId, 'title' => $suggestedPlanTitle, 'productId' => $membershipService->getPlanProductId($suggestedPlanId));
        }

        return null;
    }

    private function actionPermittedForMembershipType( $action, MEMBERSHIP_BOL_MembershipType $type, $permissions )
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

    private function getPackageTitle( $credits, $price )
    {
        $currency = BOL_BillingService::getInstance()->getActiveCurrency();

        return $credits . ' Credits for ' . $currency . ' ' . floatval($price);
    }
}