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
class SKADATEIOS_ACTRL_Photo extends OW_ApiActionController
{
    public function getList( $params )
    {
        if ( empty($params['userId']) )
        {
            $this->assign('list', array());

            return;
        }

        $userId = (int) $params['userId'];
        $selfMode = $userId == OW::getUser()->getId();
        $status = $selfMode ? null : PHOTO_BOL_PhotoDao::STATUS_APPROVED;
        $service = PHOTO_BOL_PhotoService::getInstance();

        $source = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_source", $userId);
        $source = ($source == "album") ? "album": "all";

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

        $this->assign('list', $result);

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

    public function albumPhotoList( $params )
    {
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

    public function getAlbumList( $params )
    {
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
                    'photoCount' => $count
                );
            }

            $list = $result;
        }

        $this->assign('list', $list);
    }

    public function deletePhotos( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException();
        }

        if ( empty($params['idList']) || !count($params['idList']) )
        {
            throw new ApiResponseErrorException();
        }

        $service = PHOTO_BOL_PhotoService::getInstance();
        $idList = $params['idList'];
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
    
    public function upload( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !$userId )
        {
            throw new ApiResponseErrorException("Undefined userId");
        }

        if ( empty($_FILES['images']) )
        {
            throw new ApiResponseErrorException("Files were not uploaded");
        }

        $files = $_FILES['images'];

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
                foreach ( $uploadedList as $photo )
                {
                    $result[] = self::preparePhotoData($photo->id, $photo->hash, $photo->dimension, $photo->status);
                }
            }
        }

        $this->assign("uploaded", $result);
    }

    public static function preparePhotoData( $id, $hash, $dimensions = array(), $status = null )
    {
        $isPhotoActive = OW::getPluginManager()->isPluginActive("photo");
        $result['id'] = $id;

        $thumbKey = $isPhotoActive ? PHOTO_BOL_PhotoService::TYPE_SMALL : "small";
        $galleryKey = $isPhotoActive ? PHOTO_BOL_PhotoService::TYPE_PREVIEW : "preview";
        $mainKey = $isPhotoActive ? PHOTO_BOL_PhotoService::TYPE_MAIN : "main";
        $smallWidth = $isPhotoActive ? PHOTO_BOL_PhotoService::DIM_SMALL_WIDTH : 200;
        $smallHeight = $isPhotoActive ? PHOTO_BOL_PhotoService::DIM_SMALL_HEIGHT : 200;

        $dimensions = !empty($dimensions) ? json_decode($dimensions, true) : null;
        $hasGallerySize = isset($dimensions[$galleryKey][0]) && isset($dimensions[$galleryKey][1]);
        $hasMainSize = isset($dimensions[$mainKey][0]) && isset($dimensions[$mainKey][1]);

        // thumb
        if ( $isPhotoActive && $id && $hash )
        {
            $result['thumbUrl'] = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrlByType($id, $thumbKey, $hash);
        }
        
        $result['thumbWidth'] = !empty($dimensions[$thumbKey][0]) ? $dimensions[$thumbKey][0] : $smallWidth;
        $result['thumbHeight'] = !empty($dimensions[$thumbKey][1]) ? $dimensions[$thumbKey][1] : $smallHeight;

        // gallery
        if ( $isPhotoActive && $id && $hash )
        {
            $result['galleryUrl'] = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrlByType($id, $hasGallerySize ? $galleryKey : $thumbKey, $hash);
        }
        
        $result['galleryWidth'] = $hasGallerySize ? $dimensions[$galleryKey][0] : $result['thumbWidth'];
        $result['galleryHeight'] = $hasGallerySize ? $dimensions[$galleryKey][1] : $result['thumbHeight'];

        // main
        if ( $isPhotoActive && $id && $hash )
        {
            $result['mainUrl'] = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrlByType($id, $hasMainSize ? $mainKey : $thumbKey, $hash);
        }
        
        $result['mainWidth'] = $hasMainSize ? $dimensions[$mainKey][0] : $result['thumbWidth'];
        $result['mainHeight'] = $hasMainSize ? $dimensions[$mainKey][1] : $result['thumbHeight'];
        
        $result["approval"] = $status == "approval" ? 1 : 0;

        return $result;
    }
}