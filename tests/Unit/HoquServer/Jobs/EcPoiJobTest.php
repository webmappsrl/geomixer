<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Console\Commands\HoquServer;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\EcPoiJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class EcPoiJobTest extends TestCase
{
    use RefreshDatabase;

    public function loadDem()
    {
        static $first = true;
        if ($first) {
            Artisan::call('geomixer:import_dem', ['name' => 'pisa_dem_100mx100m.sql']);
            $first = false;
        }
    }

    public function testJobExecuted()
    {
        $jobParameters = [
            'id' => 1,
        ];
        $job = [
            'id' => 1,
            'job' => ENRICH_EC_POI,
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

        $this->mock(EcPoiJobsServiceProvider::class, function ($mock) use ($jobParameters) {
            $mock->shouldReceive('enrichJob')
                ->with($jobParameters)
                ->once()
                ->andReturn();
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertTrue($result);
    }

    public function testPoiEleCalculation()
    {
        $this->loadDem();
        $poiId = 1;
        $params = ['id' => $poiId];
        $ecPoiService = $this->partialMock(EcPoiJobsServiceProvider::class);
        $ecPoi = [
            'type' => 'Feature',
            'properties' => [
                'id' => $poiId,
                'ele' => -100,
            ],
            'geometry' => [
                'type' => 'Point',
                "coordinates" => [
                    10.495,
                    43.758
                ]
            ]
        ];

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecPoi, $poiId) {

            $mock->shouldReceive('getEcPoi')
                ->with($poiId)
                ->once()
                ->andReturn($ecPoi);

            $mock->shouldReceive('_executePutCurl')
                ->with(
                    Mockery::on(function () {
                        return true;
                    }),
                    Mockery::on(function ($payload) {
                        return 776 == $payload['ele'];
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecPoiService->enrichJob($params);
    }
}
