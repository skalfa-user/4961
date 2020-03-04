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

/**
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_system_plugins.skadateios.api.controllers
 * @since 1.0
 */
class SKANDROID_ACTRL_Photo extends OW_ApiActionController
{
    public function getList( $post, $params )
    {
        $userId = (int) $params["id"];
        $service = PHOTO_BOL_PhotoService::getInstance();
        $selfMode = $userId == OW::getUser()->getId();
        $status = $selfMode ? null : PHOTO_BOL_PhotoDao::STATUS_APPROVED;

        $auth = array(
            'photo_view' => SKANDROID_ABOL_Service::getInstance()->getAuthorizationActionStatus('photo', 'view'),
            'photo_upload' => SKANDROID_ABOL_Service::getInstance()->getAuthorizationActionStatus('photo', 'upload')
        );

        $this->assign('auth', $auth);
        
        $source = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_source", $userId);
        $source = ($source == "album") ? "album": "all";
        $source = "all";
        $result = array();
        if ( $source == "album" )
        {
            $selectedAlbumId = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_album", $userId);

            if ( !$selectedAlbumId )
            {
                $source = "all";
            }
            else
            {
                $list = $service->getAlbumPhotos($selectedAlbumId, 1, 500, null, $status);
                if ( $list )
                {
                    foreach ( $list as $photo )
                    {
                        $result[] = self::preparePhotoData($photo['dto']->id, $photo['dto']->hash, $photo['dto']->dimension, $photo['dto']->status);
                    }
                }
            }
        }

        if ( $source == "all" )
        {
            $list = $service->findPhotoListByUserId($userId, 1, 500, array(), $status);
            if ( $list )
            {
                foreach ( $list as $photo )
                {
                    $result[] = self::preparePhotoData($photo['id'], $photo['hash'], $photo['dimension'], $photo['status']);
                }
            }
        }

        $this->assign('list', array_slice($result, 0, 30));

        $albumService = PHOTO_BOL_PhotoAlbumService::getInstance();
        $albumList = $list = $albumService->findUserAlbumList($userId, 1, 500, null, true);
        $albumCount = 0;
        if ( $albumList )
        {
            foreach ( $albumList as $album )
            {
                $count = isset($album['photo_count']) ? $album['photo_count'] : 0;
                if ( $count )
                {
                    $albumCount++;
                }
            }
        }

        $this->assign('albums', $albumCount);
    }

    public function getAlbumList( $post, $params )
    {
        $auth = array(
            'photo_view' => SKANDROID_ABOL_Service::getInstance()->getAuthorizationActionStatus('photo', 'view')
        );

        $this->assign('auth', $auth);
        
        if ( empty($params['userId']) )
        {
            $this->assign('list', array());

            return;
        }

        $userId = (int) $params['userId'];

        $service = PHOTO_BOL_PhotoAlbumService::getInstance();
        $list = $service->findUserAlbumList($userId, 1, 500, null, true);

        if ( $list )
        {
            $result = array();
            foreach ( $list as $album )
            {
                $count = isset($album['photo_count']) ? $album['photo_count'] : 0;
                if ( !$count )
                {
                    continue;
                }
                $result[] = array(
                    'id' => $album['dto']->id,
                    'name' => $album['dto']->name,
                    'url' => $album['cover'],
                    'photoCount' => $count,
                );
            }

            $list = $result;
        }

        $this->assign('list', $list);
    }

    public function albumPhotoList( $post, $params )
    {
        $auth = array(
            'photo_view' => SKADATEIOS_ABOL_Service::getInstance()->getAuthorizationActionStatus('photo', 'view')
        );

        $this->assign('auth', $auth);
        
        if ( empty($params['albumId']) )
        {
            $this->assign('list', array());
            return;
        }

        $albumId = (int) $params['albumId'];
        $service = PHOTO_BOL_PhotoService::getInstance();
        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
        $selfMode = $album->userId == OW::getUser()->getId();
        $status = $selfMode ? null : PHOTO_BOL_PhotoDao::STATUS_APPROVED;
        $list = $service->getAlbumPhotos($albumId, 1, 500, null, $status);

        if ( $list )
        {
            $result = array();
            foreach ( $list as $photo )
            {
                $result[] = self::preparePhotoData($photo['dto']->id, $photo['dto']->hash, $photo['dto']->dimension, $photo['dto']->status);
            }

            $list = $result;
        }

        $this->assign('list', $list);
    }

    public function upload( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException("Undefined userId");
        }

        if ( empty($_FILES['file']) )
        {
            throw new ApiResponseErrorException("Files were not uploaded");
        }

        $files = array("tmp_name" => array($_FILES['file']["tmp_name"]));

        $selectedAlbumId = null;
        $source = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_source", $userId);
        $source = ($source == "album") ? "album": "all";

        if ( $source == "album" )
        {
            $selectedAlbumId = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_album", $userId);

            if ( !$selectedAlbumId )
            {
                $source = "all";
            }
        }

        if ( $source == "all" )
        {
            $event = new OW_Event('photo.getMainAlbum', array('userId' => $userId));
            OW::getEventManager()->trigger($event);
            $album = $event->getData();

            $selectedAlbumId = !empty($album['album']) ? $album['album']['id'] : null;
        }

        if( !$selectedAlbumId && isset($_POST["albumId"]) )
        {
            $selectedAlbumId = (int)$_POST["albumId"];
        }

        if ( !$selectedAlbumId )
        {
            throw new ApiResponseErrorException("Undefined album");
        }

        $uploadedIdList = array();

        foreach ( $files['tmp_name'] as $path )
        {
            $photo = OW::getEventManager()->call('photo.add', array(
                'albumId' => $selectedAlbumId,
                'path' => $path
            ));

            if ( !empty($photo['photoId']) )
            {
                $uploadedIdList[] = $photo['photoId'];
                BOL_AuthorizationService::getInstance()->trackActionForUser($userId, 'photo', 'upload');
            }
        }

        $result = array();
        if ( $uploadedIdList )
        {
            $uploadedList = PHOTO_BOL_PhotoDao::getInstance()->findByIdList($uploadedIdList);
            
            if ( $uploadedList )
            {
                /* @var $photo PHOTO_BOL_Photo */
                foreach ( $uploadedList as $photo )
                {
                    $result[] = self::preparePhotoData($photo->id, $photo->hash, $photo->dimension, $photo->status);
                }
            }
        }

        $this->assign("uploaded", array($photo, $result));
    }

    public function deletePhotos( $post, $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($post['idList']) )
        {
            throw new ApiResponseErrorException();
        }

        $service = PHOTO_BOL_PhotoService::getInstance();
        $idList = explode(",", $post['idList']);

        $deletedList = array();

        foreach ( $idList as $id )
        {
            $owner = $service->findPhotoOwner($id);
            if ( $owner != $userId )
            {
                continue;
            }

            if ( $service->deletePhoto($id) )
            {
                $deletedList[] = $id;
            }
        }

        $this->assign('deleted', $deletedList);
    }

    /*------------------------------------------------------------------*/
    
    public static function preparePhotoData( $id, $hash, $dimensions = array(), $status = null )
    {
        $service = PHOTO_BOL_PhotoService::getInstance();

        $result['id'] = $id;

        $thumbKey = PHOTO_BOL_PhotoService::TYPE_SMALL;
        $galleryKey = PHOTO_BOL_PhotoService::TYPE_PREVIEW;
        $mainKey = PHOTO_BOL_PhotoService::TYPE_MAIN;

        $dimensions = !empty($dimensions) ? json_decode($dimensions, true) : null;
        $hasGallerySize = isset($dimensions[$galleryKey][0]) && isset($dimensions[$galleryKey][1]);
        $hasMainSize = isset($dimensions[$mainKey][0]) && isset($dimensions[$mainKey][1]);

        // thumb
        if ( $id && $hash )
        {
            $result['thumbUrl'] = $service->getPhotoUrlByType($id, $thumbKey, $hash);
        }
        $result['thumbWidth'] = !empty($dimensions[$thumbKey][0]) ? $dimensions[$thumbKey][0] : PHOTO_BOL_PhotoService::DIM_SMALL_WIDTH;
        $result['thumbHeight'] = !empty($dimensions[$thumbKey][1]) ? $dimensions[$thumbKey][1] : PHOTO_BOL_PhotoService::DIM_SMALL_HEIGHT;

        // gallery
        if ( $id && $hash )
        {
            $result['galleryUrl'] = $service->getPhotoUrlByType($id, $hasGallerySize ? $galleryKey : $thumbKey, $hash);
        }
        $result['galleryWidth'] = $hasGallerySize ? $dimensions[$galleryKey][0] : $result['thumbWidth'];
        $result['galleryHeight'] = $hasGallerySize ? $dimensions[$galleryKey][1] : $result['thumbHeight'];

        // main
        if ( $id && $hash )
        {
            $result['mainUrl'] = $service->getPhotoUrlByType($id, $hasMainSize ? $mainKey : $thumbKey, $hash);
        }
        $result['mainWidth'] = $hasMainSize ? $dimensions[$mainKey][0] : $result['thumbWidth'];
        $result['mainHeight'] = $hasMainSize ? $dimensions[$mainKey][1] : $result['thumbHeight'];

        $result["approved"] = $status == PHOTO_BOL_PhotoDao::STATUS_APPROVED ? true : false;

        return $result;
    }
}