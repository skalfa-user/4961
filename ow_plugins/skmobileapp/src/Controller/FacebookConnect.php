<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */
namespace Skadate\Mobile\Controller;

use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Exception;
use OW;

class FacebookConnect extends Base
{
    /**
     * Connect methods
     *
     * @param SilexApplication $app
     * @return mixed
     */
    public function connect(SilexApplication $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        // login
        $controllers->post('/', function(Request $request) use ($app) {
            if (!OW::getPluginManager()->isPluginActive('fbconnect')) {
                throw new BadRequestHttpException('Facebook plugin is not activated');
            }

            $data = json_decode($request->getContent(), true);

            if (empty($data['authResponse']['userID']) || empty($data['authResponse']['accessToken'])) {
                throw new BadRequestHttpException('Some important params are missing');
            }

            $response = [
                'isSuccess' => false,
                'token' => '',
                'action' => ''
            ];

            try {
                list($action, $userId) = $this->service->
                        facebookConnect($data['authResponse']['userID'], $data['authResponse']['accessToken']);

                $userDto = $this->userService->findUserById($userId);

                $response['isSuccess'] = true;
                $response['token'] = $app['security.jwt.encoder']->encode($this->service->getUserDataForToken($userId));
                $response['action'] = $action;
            }
            catch (Exception $e) {}

            return $app->json($response);
        });

        return $controllers;
    }
}
