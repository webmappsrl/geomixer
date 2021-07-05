<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Console\Commands\HoquServer;
use App\Models\EcMedia;
use App\Models\TaxonomyWhere;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\EcMediaJobsServiceProvider;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exception\ImageException;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Tests\TestCase;

class EcMediaJobTest extends TestCase
{
    public function testJobExecuted()
    {
        $jobParameters = [
            'id' => 1,
        ];
        $job = [
            'id' => 1,
            'job' => ENRICH_EC_MEDIA,
            'parameters' => json_encode($jobParameters)
        ];
        $hoquServiceMock = $this->mock(HoquServiceProvider::class, function ($mock) use ($job) {
            $mock->shouldReceive('pull')
                ->once()
                ->andReturn($job);

            $mock->shouldReceive('updateDone')
                ->once()
                ->andReturn(200);
        });

        $this->mock(EcMediaJobsServiceProvider::class, function ($mock) use ($jobParameters) {
            $mock->shouldReceive('enrichJob')
                ->once()
                ->andReturn();
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertTrue($result);
    }

    public function testGetExifDataJpg()
    {
        $ecMediaJobsServiceProvider = $this->partialMock(EcMediaJobsServiceProvider::class);

        $exif_data = $ecMediaJobsServiceProvider->getImageExif(base_path() . '/tests/Fixtures/EcMedia/test.jpg');

        $this->assertTrue(is_array($exif_data));
        $this->assertTrue(is_array($exif_data['coordinates']));
        $this->assertCount(2, $exif_data['coordinates']);
        $this->assertIsNumeric($exif_data['coordinates'][0]); //longintude
        $this->assertIsNumeric($exif_data['coordinates'][1]); //latitude
        $this->assertEquals(10.448261111111, $exif_data['coordinates'][0]);
        $this->assertEquals(43.781288888889, $exif_data['coordinates'][1]);
    }

    public function _testImageNoExists()
    {
        $ecMediaJobsServiceProvider = $this->partialMock(EcMediaJobsServiceProvider::class);
        $image = base_path() . '/tests/Fixtures/EcMedia/test2.jpg';
        try {
            $ecMediaJobsServiceProvider->imgResize($image, 100, 100);
        } catch (Exception $e) {
        }
    }

    public function testImageExists()
    {
        $image = base_path() . '/tests/Fixtures/EcMedia/test.jpg';
        $this->assertFileExists($image);
    }

    public function testImageResize()
    {
        $thumbnailSizes = [
            ['width' => 108, 'height' => 148],
            ['width' => 108, 'height' => 137],
            ['width' => 225, 'height' => 100],
            ['width' => 118, 'height' => 138],
            ['width' => 108, 'height' => 139],
            ['width' => 118, 'height' => 117],
        ];

        $image = base_path() . '/tests/Fixtures/EcMedia/test.jpg';
        $ecMediaJobsServiceProvider = $this->partialMock(EcMediaJobsServiceProvider::class);
        foreach ($thumbnailSizes as $size) {
            $resizedFileName = base_path() . '/tests/Fixtures/EcMedia/' . $ecMediaJobsServiceProvider->resizedFileName($image, $size['width'], $size['height']);
            $ecMediaJobsServiceProvider->imgResize($image, $size['width'], $size['height']);
            $cloudImage = $ecMediaJobsServiceProvider->uploadEcMediaImageResize($resizedFileName, $size['width'], $size['height']);
            $this->assertFileExists($resizedFileName);
            $headers = get_headers($cloudImage);
            $this->assertTrue(stripos($headers[0], "200 OK") >= 0);
        }
    }

    public function testImageResizeTooSmall()
    {
        $thumbnailSize = ['width' => 10000, 'height' => 10000];

        $image = base_path() . '/tests/Fixtures/EcMedia/test.jpg';
        $ecMediaJobsServiceProvider = $this->partialMock(EcMediaJobsServiceProvider::class);
        $resizedFileName = base_path() . '/tests/Fixtures/EcMedia/' . $ecMediaJobsServiceProvider->resizedFileName($image, $thumbnailSize['width'], $thumbnailSize['height']);

        try {
            $ecMediaJobsServiceProvider->imgResize($image, $thumbnailSize['width'], $thumbnailSize['height']);
        } catch (ImageException $e) {
            $this->assertFileDoesNotExist($resizedFileName);

            return;
        }

        $this->fail("The image should not be resized correctly but something went right");
    }

    public function testDeleteAwsImagesWhenDeleteMedia()
    {
        Storage::disk('s3')->put('EcMedia/', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test.jpg'));
        Storage::disk('s3')->put('EcMedia/Resize/108x137/', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_108x137.jpg'));
        Storage::disk('s3')->put('EcMedia/Resize/108x139/', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_108x139.jpg'));
        Storage::disk('s3')->put('EcMedia/Resize/118x117/', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_118x117.jpg'));
        Storage::disk('s3')->put('EcMedia/Resize/118x138/', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_118x138.jpg'));
        Storage::disk('s3')->put('EcMedia/Resize/225x100/', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_225x100.jpg'));
        $ecMediaJobsServiceProvider = $this->partialMock(EcMediaJobsServiceProvider::class);
        $url = 'https://ecmedia.s3.eu-central-1.amazonaws.com/EcMedia/26.jpg';
        $thumbnails = '{"108x148":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x148\/test_108x148.jpg",
        "108x137":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x137\/test_108x137.jpg",
        "225x100":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/225x100\/test_225x100.jpg",
        "118x138":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x138\/test_118x138.jpg",
        "108x139":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x139\/test_108x139.jpg",
        "118x117":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x117\/test_118x117.jpg"}';
        $params = ['url' => $url,
            'thumbnails' => $thumbnails];
        $ecMediaJobsServiceProvider->deleteImagesJob($params);

        $headers = get_headers(Storage::cloud()->url('EcMedia/test.jpg'));
        $this->assertEquals($headers[0], 'HTTP/1.1 404 Not Found');
        
    }
}
