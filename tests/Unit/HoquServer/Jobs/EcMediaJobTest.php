<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Console\Commands\HoquServer;
use App\Models\EcMedia;
use App\Models\TaxonomyWhere;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\EcMediaJobsServiceProvider;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
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

        $this->mock(EcMediaJobsServiceProvider::class, function ($mock) use ($job) {
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


}
