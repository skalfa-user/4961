<?php

/**
 * Copyright (c) 2021, Skalfa LLC
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
use PHOTVER_BOL_Service;
use BOL_UserService;
use OW;

class VerifyPhoto extends Base
{
    /**
     * Avatars constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

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

        // create photo
        $controllers->post('/', function (SilexApplication $app) {
            // check uploaded file
            if (empty($_FILES['file']['tmp_name'])) {
                throw new BadRequestHttpException('File was not uploaded');
            }

            // validate photo
            if (! PHOTVER_BOL_Service::getInstance()->isAvatarValid($_FILES['file']['type'], $_FILES['file']['size']))
            {
                throw new BadRequestHttpException('File has wrong format or big size');
            }

            $file = $_FILES['file']['tmp_name'];

            $result = PHOTVER_BOL_Service::getInstance()->saveTmpPhoto($file);

            if (!empty($result['url']) && !empty($result['key']))
            {
                return $app->json([
                    'url' => $result['url'],
                    'key' => $result['key']
                ]);
            }

            throw new BadRequestHttpException('File was not uploaded');
        });

        // post verify photo
        $controllers->post('/post-photo/', function (Request $request, SilexApplication $app) {
            $data = json_decode($request->getContent(), true);

            if(BOL_UserService::getInstance()->findUserById($data['id'])
                    && OW::getPluginManager()->isPluginActive('photver'))
            {
                PHOTVER_BOL_Service::getInstance()->markVerificationPhotoStepApp(
                    $data['id'],
                    $data['key']
                );

                PHOTVER_BOL_Service::getInstance()->deleteDeclineReason($data['id']);

                return $app->json([], 204);
            }

            throw new BadRequestHttpException('File was not uploaded');
        });

        return $controllers;
    }
}
