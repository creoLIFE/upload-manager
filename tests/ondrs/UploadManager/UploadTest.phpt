<?php


use ondrs\UploadManager\Upload;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


class UploadTest extends Tester\TestCase
{

    /** @var  \Mockista\Mock */
    private $imageManager;

    /** @var  \Mockista\Mock */
    private $fileManager;

    /** @var  \Mockista\Mock */
    private $httpRequest;

    /** @var Upload */
    private $upload;


    function setUp()
    {
        $file = new \SplFileInfo(__DIR__ . '/data/test-file.txt');
        $image = new \SplFileInfo(__DIR__ . '/data/test-image.jpg');

        $this->fileManager = \Mockista\mock('ondrs\UploadManager\FileManager');
        $this->fileManager->expects('upload')
            ->andReturn($file);

        $this->imageManager = \Mockista\mock('ondrs\UploadManager\ImageManager');
        $this->imageManager->expects('upload')
            ->andReturn($image);

        $this->httpRequest = \Mockista\mock('Nette\Http\Request');
        $this->httpRequest->expects('getQuery')
            ->andReturn(NULL);

        $this->upload = new Upload($this->imageManager, $this->fileManager, $this->httpRequest);
    }


    function testListenOnImagesUpload()
    {
        $filePath = TEMP_DIR . '/test-image.jpg';

        copy(__DIR__ . '/data/test-image.jpg', $filePath);

        $file = new \SplFileInfo($filePath);

        $fileUpload = new \Nette\Http\FileUpload([
            'name' => $file->getBasename(),
            'type' => $file->getType(),
            'size' => $file->getSize(),
            'tmp_name' => $filePath,
            'error' => 0
        ]);

        $this->httpRequest->expects('getFiles')
            ->andReturn([$fileUpload, $fileUpload]);

        $this->imageManager->expects('getRelativePath')
            ->andReturn('');

        $this->upload->onFileComplete[] = function (\Nette\Http\FileUpload $upload, \SplFileInfo $uploadedFile, $dir) use ($fileUpload) {
            Assert::same($fileUpload, $upload);
            Assert::true($fileUpload->isImage());
            Assert::equal('test-image.jpg', $uploadedFile->getBasename());
        };

        $this->upload->filesToDir();

        $this->imageManager->assertExpectations();
    }


    function testListenOnFileUpload()
    {
        $filePath = TEMP_DIR . '/test-file.txt';

        copy(__DIR__ . '/data/test-file.txt', $filePath);

        $file = new \SplFileInfo($filePath);

        $fileUpload = new \Nette\Http\FileUpload([
            'name' => $file->getBasename(),
            'type' => $file->getType(),
            'size' => $file->getSize(),
            'tmp_name' => $filePath,
            'error' => 0
        ]);

        $this->httpRequest->expects('getFiles')
            ->andReturn([$fileUpload, $fileUpload]);

        $this->fileManager->expects('getRelativePath')
            ->andReturn('');

        $this->upload->onQueueBegin[] = function(array $files) {
            Assert::count(2, $files);
        };

        $this->upload->onQueueComplete[] = function(array $files, array $uploaded) {
            Assert::count(2, $files);
            Assert::count(2, $uploaded);
        };

        $this->upload->onFileBegin[] = function (\Nette\Http\FileUpload $upload, $dir) use ($fileUpload) {
            Assert::same($fileUpload, $upload);
        };

        $this->upload->onFileComplete[] = function (\Nette\Http\FileUpload $upload, \SplFileInfo $uploadedFile, $dir) use ($fileUpload) {
            Assert::same($fileUpload, $upload);
            Assert::false($fileUpload->isImage());
            Assert::equal('test-file.txt', $uploadedFile->getBasename());
        };

        $this->upload->filesToDir();

        $this->fileManager->assertExpectations();
    }


    function testUploadSingleFile()
    {
        $filePath = TEMP_DIR . '/test-file.txt';

        copy(__DIR__ . '/data/test-file.txt', $filePath);

        $file = new \SplFileInfo($filePath);

        $fileUpload = new \Nette\Http\FileUpload([
            'name' => $file->getBasename(),
            'type' => $file->getType(),
            'size' => $file->getSize(),
            'tmp_name' => $filePath,
            'error' => 0
        ]);

        $this->fileManager->expects('getRelativePath')
            ->andReturn('');

        $splInfo = $this->upload->singleFileToDir($fileUpload);

        Assert::false($fileUpload->isImage());
        Assert::true($splInfo instanceof \SplFileInfo);
        Assert::same('test-file.txt', $splInfo ->getBasename());

        $this->fileManager->assertExpectations();
    }


    function testUploadSingleImage()
    {
        $filePath = TEMP_DIR . '/test-image.jpg';

        copy(__DIR__ . '/data/test-image.jpg', $filePath);

        $file = new \SplFileInfo($filePath);

        $fileUpload = new \Nette\Http\FileUpload([
            'name' => $file->getBasename(),
            'type' => $file->getType(),
            'size' => $file->getSize(),
            'tmp_name' => $filePath,
            'error' => 0
        ]);

        $this->imageManager->expects('getRelativePath')
            ->andReturn('');

        $splInfo = $this->upload->singleFileToDir($fileUpload);

        Assert::true($fileUpload->isImage());
        Assert::true($splInfo instanceof \SplFileInfo);
        Assert::same('test-image.jpg', $splInfo ->getBasename());

        $this->fileManager->assertExpectations();
    }


}


run(new UploadTest());
