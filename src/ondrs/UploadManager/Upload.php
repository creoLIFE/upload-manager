<?php

namespace ondrs\UploadManager;


use Nette\Http\FileUpload;
use Nette\Http\Request;
use Nette\Object;
use SplFileInfo;


class Upload extends Object
{

    /** @var Request */
    private $httpRequest;

    /** @var ImageManager */
    private $imageManager;

    /** @var FileManager */
    private $fileManager;

    /** @var array */
    public $onQueueBegin = [];

    /** @var array */
    public $onQueueComplete = [];

    /** @var array */
    public $onFileBegin = [];

    /** @var array */
    public $onFileComplete = [];

    /** @var array */
    public $onError = [];


    /**
     * @param ImageManager $imageManager
     * @param FileManager $fileManager
     * @param Request $request
     */
    public function __construct(ImageManager $imageManager, FileManager $fileManager, Request $request)
    {
        $this->imageManager = $imageManager;
        $this->fileManager = $fileManager;
        $this->httpRequest = $request;
    }

    /**
     * @param FileManager $fileManager
     */
    public function setFileManager($fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @return FileManager
     */
    public function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @param ImageManager $imageManager
     */
    public function setImageManager($imageManager)
    {
        $this->imageManager = $imageManager;
    }

    /**
     * @return ImageManager
     */
    public function getImageManager()
    {
        return $this->imageManager;
    }


    /**
     * @param NULL|string $dir
     * @return SplFileInfo[]
     */
    public function filesToDir($dir = NULL)
    {
        $uploadedFiles = [];

        $this->onQueueBegin($this->httpRequest->getFiles());

        foreach ($this->httpRequest->getFiles() as $file) {

            if (is_array($file)) {

                foreach ($file as $f) {
                    try {
                        $uploadedFiles[] = $this->singleFileToDir($f, $dir);
                    } catch (UploadErrorException $e) {
                        $this->onError($f, $e);
                    }
                }

            } else {
                try {
                    $uploadedFiles[] = $this->singleFileToDir($file, $dir);
                } catch (UploadErrorException $e) {
                    $this->onError($file, $e);
                }
            }
        }

        $this->onQueueComplete($this->httpRequest->getFiles(), $uploadedFiles);

        return $uploadedFiles;
    }


    /**
     * @param FileUpload $fileUpload
     * @param NULL|string $dir
     * @return SplFileInfo
     * @throws UploadErrorException
     */
    public function singleFileToDir(FileUpload $fileUpload, $dir = NULL)
    {
        if ($error = $fileUpload->getError()) {
            throw new UploadErrorException($error);
        }

        $name = $fileUpload->isImage() ? 'imageManager' : 'fileManager';

        /** @var IUploadManager $usedManager */
        $usedManager = $this->$name;
        $path = Utils::normalizePath($usedManager->getRelativePath() . '/' . $dir);

        $this->onFileBegin($fileUpload, $path);

        $uploadedFile = $usedManager->upload($fileUpload, $dir);

        $this->onFileComplete($fileUpload, $uploadedFile, $path);

        return $uploadedFile;
    }


} 
