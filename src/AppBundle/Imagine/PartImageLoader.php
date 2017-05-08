<?php

namespace AppBundle\Imagine;

use AppBundle\Api\Manager\RebrickableManager;
use League\Flysystem\Filesystem;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;

class PartImageLoader extends BaseImageLoader
{
    /** @var Filesystem */
    private $mediaFilesystem;

    /** @var RebrickableManager */
    private $rebrickableManager;

    private $rebrickableContext = 'http://rebrickable.com/media/parts/ldraw/';

    /**
     * PartImageLoader constructor.
     *
     * @param $rebrickableManager
     * @param $mediaFilesystem
     */
    public function __construct($rebrickableManager, $mediaFilesystem)
    {
        $this->mediaFilesystem = $mediaFilesystem;
        $this->rebrickableManager = $rebrickableManager;
    }

    public function find($path)
    {
        // try to load image from local mediaFilesystem
        if ($this->mediaFilesystem->has('/images/'.$path)) {
            return $this->mediaFilesystem->read('/images/'.$path);
        }

        // try to load image from rebrickable website
        try {
            if ($this->remoteFileExists($this->rebrickableContext.$path)) {
                return file_get_contents($this->rebrickableContext.$path);
            }
        } catch (\Exception $e) {
            throw new NotLoadableException(sprintf('Source image %s could not be loaded.', $path), $e->getCode(), $e);
        }

        // Load part entity form rebrickable api and get image path from response
        try {
            if (preg_match('/^(.*)\/(.*).png$/', $path, $match)) {
                $part = $this->rebrickableManager->getPart($match[2]);

                if ($part && $part->getImgUrl()) {
                    return file_get_contents($part->getImgUrl());
                }
            }
        } catch (\Exception $e) {
            throw new NotLoadableException(sprintf('Source image %s could not be loaded.', $path), $e->getCode(), $e);
        }

        return $this->mediaFilesystem->read('noimage.png');
    }
}
