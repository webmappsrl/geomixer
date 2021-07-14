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

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {

            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('_executePutCurl')
                ->with(
                    Mockery::on(function () {
                        return true;
                    }),
                    Mockery::on(function ($payload) {
                        return isset($payload['ele_max'])
                            && $payload['ele_max'] == 776;
                    })
                )
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

    public function testAscent()
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
                        10.440509,
                        43.764120,
                    ],
                    [
                        10.442032,
                        43.765654,
                    ],
                    [
                        10.442891,
                        43.767065,
                    ],
                    [
                        10.443770,
                        43.768800,
                    ],
                    [
                        10.444521,
                        43.769761,
                    ],
                    [
                        10.445637,
                        43.770566,
                    ],
                    [
                        10.447204,
                        43.771496,
                    ],
                    [
                        10.448534,
                        43.772457,
                    ],
                    [
                        10.449156,
                        43.772767,
                    ],
                    [
                        10.449907,
                        43.772953,
                    ],
                    [
                        10.451173,
                        43.772643,
                    ],
                    [
                        10.452096,
                        43.771636,
                    ],
                    [
                        10.452804,
                        43.769776,
                    ],
                    [
                        10.453169,
                        43.768118,
                    ],
                    [
                        10.453362,
                        43.766553,
                    ],
                    [
                        10.453062,
                        43.765267,
                    ],
                    [
                        10.451045,
                        43.763841,
                    ],
                    [
                        10.449178,
                        43.763764,
                    ]
                ]
            ]
        ];

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {

            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('_executePutCurl')
                ->with(
                    Mockery::on(function () {
                        return true;
                    }),
                    Mockery::on(function ($payload) {
                        return isset($payload['ascent'])
                            && $payload['ascent'] == 312;
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);
    }

    public function testDescent()
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
                        10.440509,
                        43.764120,
                    ],
                    [
                        10.442032,
                        43.765654,
                    ],
                    [
                        10.442891,
                        43.767065,
                    ],
                    [
                        10.443770,
                        43.768800,
                    ],
                    [
                        10.444521,
                        43.769761,
                    ],
                    [
                        10.445637,
                        43.770566,
                    ],
                    [
                        10.447204,
                        43.771496,
                    ],
                    [
                        10.448534,
                        43.772457,
                    ],
                    [
                        10.449156,
                        43.772767,
                    ],
                    [
                        10.449907,
                        43.772953,
                    ],
                    [
                        10.451173,
                        43.772643,
                    ],
                    [
                        10.452096,
                        43.771636,
                    ],
                    [
                        10.452804,
                        43.769776,
                    ],
                    [
                        10.453169,
                        43.768118,
                    ],
                    [
                        10.453362,
                        43.766553,
                    ],
                    [
                        10.453062,
                        43.765267,
                    ],
                    [
                        10.451045,
                        43.763841,
                    ],
                    [
                        10.449178,
                        43.763764,
                    ]
                ]
            ]
        ];

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {

            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('_executePutCurl')
                ->with(
                    Mockery::on(function () {
                        return true;
                    }),
                    Mockery::on(function ($payload) {
                        return isset($payload['descent'])
                            && $payload['descent'] == 241;
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);
    }

    public function testDurationForward()
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
                        10.440509,
                        43.764120,
                    ],
                    [
                        10.442032,
                        43.765654,
                    ],
                    [
                        10.442891,
                        43.767065,
                    ],
                    [
                        10.443770,
                        43.768800,
                    ],
                    [
                        10.444521,
                        43.769761,
                    ],
                    [
                        10.445637,
                        43.770566,
                    ],
                    [
                        10.447204,
                        43.771496,
                    ],
                    [
                        10.448534,
                        43.772457,
                    ],
                    [
                        10.449156,
                        43.772767,
                    ],
                    [
                        10.449907,
                        43.772953,
                    ],
                    [
                        10.451173,
                        43.772643,
                    ],
                    [
                        10.452096,
                        43.771636,
                    ],
                    [
                        10.452804,
                        43.769776,
                    ],
                    [
                        10.453169,
                        43.768118,
                    ],
                    [
                        10.453362,
                        43.766553,
                    ],
                    [
                        10.453062,
                        43.765267,
                    ],
                    [
                        10.451045,
                        43.763841,
                    ],
                    [
                        10.449178,
                        43.763764,
                    ]
                ]
            ]
        ];

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {

            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('_executePutCurl')
                ->with(
                    Mockery::on(function () {
                        return true;
                    }),
                    Mockery::on(function ($payload) {
                        return isset($payload['duration_forward']);
                        // && $payload['duration_forward'] == 0;
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);
    }

    public function testDurationBackward()
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
                        10.440509,
                        43.764120,
                    ],
                    [
                        10.442032,
                        43.765654,
                    ],
                    [
                        10.442891,
                        43.767065,
                    ],
                    [
                        10.443770,
                        43.768800,
                    ],
                    [
                        10.444521,
                        43.769761,
                    ],
                    [
                        10.445637,
                        43.770566,
                    ],
                    [
                        10.447204,
                        43.771496,
                    ],
                    [
                        10.448534,
                        43.772457,
                    ],
                    [
                        10.449156,
                        43.772767,
                    ],
                    [
                        10.449907,
                        43.772953,
                    ],
                    [
                        10.451173,
                        43.772643,
                    ],
                    [
                        10.452096,
                        43.771636,
                    ],
                    [
                        10.452804,
                        43.769776,
                    ],
                    [
                        10.453169,
                        43.768118,
                    ],
                    [
                        10.453362,
                        43.766553,
                    ],
                    [
                        10.453062,
                        43.765267,
                    ],
                    [
                        10.451045,
                        43.763841,
                    ],
                    [
                        10.449178,
                        43.763764,
                    ]
                ]
            ]
        ];

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {

            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('_executePutCurl')
                ->with(
                    Mockery::on(function () {
                        return true;
                    }),
                    Mockery::on(function ($payload) {
                        return isset($payload['duration_backward']);
//                            && $payload['duration_backward'] == 0;
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);
    }

    public function testAllEleInfo()
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
                        10.440509,
                        43.764120,
                    ],
                    [
                        10.442032,
                        43.765654,
                    ],
                    [
                        10.442891,
                        43.767065,
                    ],
                    [
                        10.443770,
                        43.768800,
                    ],
                    [
                        10.444521,
                        43.769761,
                    ],
                    [
                        10.445637,
                        43.770566,
                    ],
                    [
                        10.447204,
                        43.771496,
                    ],
                    [
                        10.448534,
                        43.772457,
                    ],
                    [
                        10.449156,
                        43.772767,
                    ],
                    [
                        10.449907,
                        43.772953,
                    ],
                    [
                        10.451173,
                        43.772643,
                    ],
                    [
                        10.452096,
                        43.771636,
                    ],
                    [
                        10.452804,
                        43.769776,
                    ],
                    [
                        10.453169,
                        43.768118,
                    ],
                    [
                        10.453362,
                        43.766553,
                    ],
                    [
                        10.453062,
                        43.765267,
                    ],
                    [
                        10.451045,
                        43.763841,
                    ],
                    [
                        10.449178,
                        43.763764,
                    ]
                ]
            ]
        ];

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {

            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('_executePutCurl')
                ->with(
                    Mockery::on(function () {
                        return true;
                    }),
                    Mockery::on(function ($payload) {
                        return true
                            && isset($payload['distance'])
                            && isset($payload['ele_from'])
                            && isset($payload['ele_to'])
                            && isset($payload['ele_min'])
                            && isset($payload['ele_max'])
                            && isset($payload['ascent'])
                            && isset($payload['descent'])
                            && isset($payload['duration_forward'])
                            && isset($payload['duration_backward']);
                    })
                )
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

    public function testDurationByActivities()
    {
        $this->loadDem();
        $trackId = 1;
        $params = ['id' => $trackId];
        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class);
        $ecTrack = [
            'type' => 'Feature',
            'properties' => [
                'id' => $trackId,
                'duration' => [
                    'hiking' => [
                        'forward' => 0,
                        'backward' => 0,
                    ],
                    'cycling' => [
                        'forward' => 0,
                        'backward' => 0,
                    ],
                ]
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [
                        10.440509,
                        43.764120,
                    ],
                    [
                        10.442032,
                        43.765654,
                    ],
                    [
                        10.442891,
                        43.767065,
                    ],
                    [
                        10.443770,
                        43.768800,
                    ],
                    [
                        10.444521,
                        43.769761,
                    ],
                    [
                        10.445637,
                        43.770566,
                    ],
                    [
                        10.447204,
                        43.771496,
                    ],
                    [
                        10.448534,
                        43.772457,
                    ],
                    [
                        10.449156,
                        43.772767,
                    ],
                    [
                        10.449907,
                        43.772953,
                    ],
                    [
                        10.451173,
                        43.772643,
                    ],
                    [
                        10.452096,
                        43.771636,
                    ],
                    [
                        10.452804,
                        43.769776,
                    ],
                    [
                        10.453169,
                        43.768118,
                    ],
                    [
                        10.453362,
                        43.766553,
                    ],
                    [
                        10.453062,
                        43.765267,
                    ],
                    [
                        10.451045,
                        43.763841,
                    ],
                    [
                        10.449178,
                        43.763764,
                    ]
                ]
            ]
        ];

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {

            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('_executePutCurl')
                ->with(
                    Mockery::on(function () {
                        return true;
                    }),
                    Mockery::on(function ($payload) {
                        return 99 == $payload['duration']['hiking']['forward'] && 16 == $payload['duration']['cycling']['backward'];
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);
    }
}
