<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Console\Commands\HoquServer;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\EcTrackJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class EcTrackJobTest extends TestCase
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
            'job' => ENRICH_EC_TRACK,
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

        $this->mock(EcTrackJobsServiceProvider::class, function ($mock) use ($jobParameters) {
            $mock->shouldReceive('enrichJob')
                ->once()
                ->andReturn();
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertTrue($result);
    }

    public function testDistanceCalc()
    {
        $distanceQuery = "SELECT ST_Length(ST_GeomFromText('LINESTRING(11 43, 12 43, 12 44, 11 44)')) as lenght";
        $distance = DB::select(DB::raw($distanceQuery));
        $this->assertIsNumeric($distance[0]->lenght);
    }

    /**
     * 2. GEOMIXER: il job enrich_track calcola ele_max
     * 3. GEOMIXER: il job enrich_track deve chiamare API di update dell track
     */
    public function testEleMax()
    {
        $this->loadDem();
        $trackId = 1;
        $params = ['id' => $trackId];
        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class);
        $ecTrack = [
            'type' => 'Feature',
            'properties' => [
                'id' => $trackId,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [
                        10.495,
                        43.758
                    ],
                    [
                        10.447,
                        43.740
                    ]
                ]
            ]
        ];
        $this->mock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {
            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('updateEcTrack')
                ->with($trackId, Mockery::on(function ($payload) {
                    return isset($payload['ele_max'])
                        && $payload['ele_max'] == 776;
                }))
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);

    }

    /**
     * 2. GEOMIXER: il job enrich_track calcola ele_min
     * 3. GEOMIXER: il job enrich_track deve chiamare API di update dell track
     */
    public function testEleMin()
    {
        $this->loadDem();
        $trackId = 1;
        $params = ['id' => $trackId];
        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class);
        $ecTrack = [
            'type' => 'Feature',
            'properties' => [
                'id' => $trackId,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [
                        10.495,
                        43.758
                    ],
                    [
                        10.447,
                        43.740
                    ]
                ]
            ]
        ];
        $this->mock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {
            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('updateEcTrack')
                ->with($trackId, Mockery::on(function ($payload) {
                    return isset($payload['ele_min'])
                        && $payload['ele_min'] == -1;
                }))
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);

    }

    /**
     * 2. GEOMIXER: il job enrich_track calcola il 3D di una lineString
     * 3. GEOMIXER: il job enrich_track deve chiamare API di update dell track con geometria 3D calcolata
     */
    public function testAdd3D()
    {
        $this->loadDem();
        $trackId = 1;
        $params = ['id' => $trackId];
        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class);
        $ecTrack = [
            'type' => 'Feature',
            'properties' => [
                'id' => $trackId,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [
                        10.495,
                        43.758
                    ],
                    [
                        10.447,
                        43.740
                    ]
                ]
            ]
        ];
        $this->mock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {
            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('updateEcTrack')
                ->with($trackId, Mockery::on(function ($payload) {
                    return isset($payload['geometry'])
                        && $payload['geometry']['type'] == 'LineString'
                        && $payload['geometry']['coordinates'][0][0] == 10.495
                        && $payload['geometry']['coordinates'][0][1] == 43.758
                        && $payload['geometry']['coordinates'][0][2] == 776
                        && $payload['geometry']['coordinates'][1][0] == 10.447
                        && $payload['geometry']['coordinates'][1][1] == 43.740
                        && $payload['geometry']['coordinates'][1][2] == -1;
                }))
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);

    }


}
