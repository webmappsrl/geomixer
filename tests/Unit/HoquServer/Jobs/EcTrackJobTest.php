<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Console\Commands\HoquServer;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\EcTrackJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class EcTrackJobTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function loadDem() {
        static $first = true;
        if ($first) {
            Artisan::call('geomixer:import_dem', ['name' => 'pisa_dem_100mx100m.sql']);
            $first = false;
        }
    }

    public function testJobExecuted() {
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

    public function testDistanceCalc() {
        $distanceQuery = "SELECT ST_Length(ST_GeomFromText('LINESTRING(11 43, 12 43, 12 44, 11 44)')) as lenght";
        $distance = DB::select(DB::raw($distanceQuery));
        $this->assertIsNumeric($distance[0]->lenght);
    }

    /**
     * 2. GEOMIXER: il job enrich_track calcola ele_max
     * 3. GEOMIXER: il job enrich_track deve chiamare API di update dell track
     */
    public function testEleMax() {
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
    public function testEleMin() {
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

    public function testAscent() {
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

    public function testDescent() {
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

    public function testDurationForward() {
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

    public function testDurationBackward() {
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

    public function testAllEleInfo() {
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
    public function testAdd3D() {
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

    public function testDurationByActivities() {
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

    /**
     * @test
     */
    public function check_slope_calculation() {
        $geometry = [
            'type' => 'LineString',
            'coordinates' => [
                [1, 1, 0],
                [1, 1, 100],
                [1, 1, 50],
                [1, 1, 100],
                [1, 1, 50],
            ]
        ];
        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('getDistanceComp')
                ->times(5)
                ->andReturn(1);
        });

        $result = $ecTrackService->calculateSlopeValues($geometry);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals(10, $result[0]);
        $this->assertEquals(5, $result[1]);
        $this->assertEquals(0, $result[2]);
        $this->assertEquals(0, $result[3]);
        $this->assertEquals(-5, $result[4]);
    }

    public function test_slope_on_job_completion() {
        $this->loadDem();
        $trackId = 1;
        $params = ['id' => $trackId];
        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('calculateSlopeValues')
                ->once()
                ->andReturn([10, 10, 10, 10]);
        });
        $ecTrack = [
            'type' => 'Feature',
            'properties' => [
                'id' => $trackId
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
                    ]
                ]
            ]
        ];

        $this->partialMock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId) {
            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('updateEcTrack')
                ->with(
                    $trackId,
                    Mockery::on(function ($payload) {
                        return isset($payload['slope']) && is_array($payload['slope']) && count($payload['slope']) === 4;
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);
    }

    public function test_mbtiles_calculation() {
        Artisan::call('geomixer:populate_tiles_table --zoom=2,7,11');
        $geometry = [
            "type" => "LineString",
            "coordinates" => [
                [
                    10.402003526687622,
                    43.716302425298494
                ],
                [
                    10.401660203933716,
                    43.715573499122414
                ]
            ]
        ];
        $expectedMBTiles = [
            "2/2/1",
            "7/67/46",
            "11/1083/746",
        ];

        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class, function ($mock) {
        });

        $mbtiles = $ecTrackService->getMbtilesArray($geometry);

        $this->assertIsArray($mbtiles);
        $this->assertCount(count($expectedMBTiles), $mbtiles);
        foreach ($expectedMBTiles as $id) {
            $this->assertTrue(in_array($id, $mbtiles));
        }
    }

    public function test_mbtiles_for_track_outside_mbtiles_bbox() {
        Artisan::call('geomixer:populate_tiles_table --zoom=2,7,11');
        $geometry = [
            "type" => "LineString",
            "coordinates" => [
                [
                    -10,
                    -10
                ],
                [
                    -11,
                    -11
                ]
            ]
        ];
        $expectedMBTiles = [];

        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class, function ($mock) {
        });

        $mbtiles = $ecTrackService->getMbtilesArray($geometry);

        $this->assertIsArray($mbtiles);
        $this->assertCount(count($expectedMBTiles), $mbtiles);
        foreach ($expectedMBTiles as $id) {
            $this->assertTrue(in_array($id, $mbtiles));
        }
    }

    public function test_mbtiles_on_job_completion() {
        $this->loadDem();
        $trackId = 1;
        $params = ['id' => $trackId];
        $ecTrack = [
            'type' => 'Feature',
            'properties' => [
                'id' => $trackId
            ],
            'geometry' => [
                "type" => "LineString",
                "coordinates" => [
                    [
                        10.402003526687622,
                        43.716302425298494
                    ],
                    [
                        10.401660203933716,
                        43.715573499122414
                    ]
                ]
            ]
        ];
        $expectedMBTiles = [
            "2/2/1",
            "7/67/46",
            "11/1083/746",
        ];

        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class, function ($mock) use ($expectedMBTiles) {
            $mock->shouldReceive('getMbtilesArray')
                ->once()
                ->andReturn($expectedMBTiles);
        });

        $this->mock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId, $expectedMBTiles) {
            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('updateEcTrack')
                ->with(
                    $trackId,
                    Mockery::on(function ($payload) use ($expectedMBTiles) {
                        if (isset($payload['mbtiles']) && is_array($payload['mbtiles']) && count($payload['mbtiles']) === count($expectedMBTiles)) {
                            foreach ($expectedMBTiles as $value) {
                                if (!in_array($value, $payload['mbtiles'])) return false;
                            }

                            return true;
                        } else
                            return false;
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);
    }

    public function test_elevation_chart_image_generation() {
        $ecTrack = json_decode('{"type":"Feature","properties":{"id":23,"created_at":"2021-07-07T15:05:55.000000Z","updated_at":"2021-09-28T12:57:35.000000Z","name":{"it":"Percorso ciclabile per Collelungo \u2013 Pinastrellaia","en":"Cycle path to Collelungo \u2013 Pinastrellaia"},"description":{"it":"<p>L&#39;itinerario pu&ograve; essere svolto da Alberese fino alla loc. di Collelungo (percorrendo la Strada degli Ulivi da Vergheria fino a Collelungo), oppure percorrendo la ciclabile fino a Marina di Alberese e successivamente, attraverso la strada della Pinastrellaia, si raggiunge la spiaggia di Collelungo.<\/p>\n\n<p>Prima di raggiungere la spiaggia &egrave; presente una zona di sosta con rastrelliere dove &egrave; possibile lasciare le biciclette.<\/p>\n\n<p><strong>Massimo 150 partecipanti.<\/strong><\/p>\n\n<p><em><strong>Nei gruppi con almeno 20 persone la guida &egrave; obbligatoria sempre.<\/strong><\/em><\/p>\n\n<h4>Nel percorre gli itinerari in bici si raccomanda la cautela, in quanto si sta visitando un&#39;area protetta ed &egrave; presente la possibilit&agrave; di incontrare fruitori di altre modalit&agrave; di visita (a cavallo, a piedi).<\/h4>\n\n<p>&Egrave; possibile noleggiare le biciclette nei pressi del punto di partenza.<br \/>\n<br \/>\n<strong>NB: E&#39; fortemente sconsigliato il&nbsp;transito nel percorso ciclabile&nbsp;Collelungo-Pinastrellaia (Strada degli Ulivi) con il trasportino per bambini&nbsp;a seguito ( carrellino), in quanto i varchi presenti lungo il percorso ostacolano&nbsp;il passaggio di tali mezzi.<\/strong><\/p>","en":"<p>Scheduled departure from the Alberese Visitor Center, take the road of Olives from Vergheria to the Collelungo roundabout. From here you can continue to the beach, where there are racks, where you can leave your bicycles. Subsequently, Marina di Alberese can be reached via the Pinastrellaia road; return to Alberese using the cycle path. You enter the heart of the park up to Collelungo beach along the Strada degli Olivi, then cross the Pineta Granducale pinewood along the Pinastrellaia road until you reach Marina di Alberese. From here you return to the Visitor Center along the cycle path.<\/p>\n\n<p><strong>Maximum 150 participants.<\/strong><\/p>\n\n<p><strong>Transit along the Collelungo-Pinastrellaia cycle path (Strada degli Ulivi) is strongly discouraged with a child carrier (trolley), as the gates along the route make it difficult for such vehicles to pass.<\/strong><\/p>"},"excerpt":{"it":"Litinerario pu\u00f2 essere svolto da Alberese fino alla loc. di Collelungo (percorrendo la Strada degli Ulivi da Vergheria fino a Collelungo), oppure percorrendo la ciclabile fino a Marina di Alberese e successivamente, attraverso la strada della Pinastrell","en":"Scheduled departure from the Alberese Visitor Center, take the road of Olives from Vergheria to the Collelungo roundabout. From here you can continue to the beach, where there are racks, where you can leave your bicycles."},"distance_comp":19,"user_id":11,"feature_image":{"id":108,"name":{"it":"Parco Maremma - Ciclabile Collelungo - Cover","en":"Parco Maremma - Ciclabile Collelungo - Cover"},"url":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/108.jpg","api_url":"https:\/\/geohub.webmapp.it\/api\/ec\/media\/108","sizes":{"108x148":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x148\/108_108x148.jpg","108x137":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x137\/108_108x137.jpg","150x150":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/150x150\/108_150x150.jpg","225x100":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/225x100\/108_225x100.jpg","118x138":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x138\/108_118x138.jpg","108x139":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x139\/108_108x139.jpg","118x117":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x117\/108_118x117.jpg","335x250":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/335x250\/108_335x250.jpg","400x200":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/400x200\/108_400x200.jpg","1440x500":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1440x500\/108_1440x500.jpg","1920x":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1920x\/108_1920x.jpg"}},"distance":19,"ascent":224,"descent":224,"ele_from":20,"ele_to":20,"ele_min":1,"ele_max":91,"duration_forward":365,"duration_backward":365,"difficulty":{"it":"Media","en":"Medium"},"slope":"[0,-4.8,-1.2,-0.6,0,0,-12,-10.6,0,-0.6,-0.5,0,0,0,0,-1.3,-1.1,-1,-1.1,0,0,0,0,1.2,1.2,0,0,0,-1.2,-1.7,0,0,0,0,0,-1.1,-0.4,0,-0.9,-3.4,0,0,0,0,0,0,0,0,0,0,-0.4,-0.8,0,0,0,2,1.3,2.5,8.7,0,0,0,0,-11.4,-14.7,0,-1,-0.9,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,-0.2,0,0.3,0,0,0,0,0,-0.8,-1.7,0,0,0,0,0,0,0,0,0,0,1.3,2.3,0,0,0,0,0,7.5,5.1,-1.6,-1.1,-0.6,0,0,0,0,0,0,11.4,11.6,0,0,0,0,0,0,0,-1.6,-0.4,0,0,0,0,13.5,11.5,0,1.2,1.1,6.5,3.5,1,1.3,0,0,0,0,0,0,2.7,4.5,0,0,0,2.1,2.4,0,0,-2.1,-2.2,-0.9,0,0,0,0,0,-2.9,-3.5,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,8,4.3,0,0,0,0,0,5.4,4.4,0,0,-0.4,-0.7,0,-2.8,-1.6,0,0,0,0,0,0,0,0,-1.2,-1.4,1,0.9,0,1,1.2,-1.6,-1.5,0,0,0,1,0,-0.9,0,0,0,0,1,0.6,-1.1,-1,0,0.6,0,0,0,-0.5,-0.4,0,0,1.1,2.8,0,0,6.9,2.4,-2.2,0,4.2,0,-2.1,-1.3,0,0,0,0,0,-0.8,-0.9,0,5.4,8.9,-3.7,0,0,0,-4.1,-3,0,0,0,0,0,0,0,0,0,0,1.3,0.7,0,0,0,0,0,15.7,8.8,0,0,-22,-14.2,5.9,6.9,0,0,12.4,9.1,0,42,37.6,0,0,0,9.6,6.1,0,14,16.4,0,0,0,0,0,0,0,16.1,18.2,0,0,0,0,0,0,0,0,0,36.5,6,-33.3,0,0,0,0,0,0,0,0,0,0,0,-39.5,-31.2,15.9,0,0,0,-5.4,-6.1,0,-4.5,-5.4,0,-2.2,-2.3,24.3,20.9,0,0,6,5.5,0,47,56.4,0,0,0,0,0,0,0,0,0,0,15.3,8.8,2,-7.1,-12.1,0,0,-3.5,-4.7,-2.9,-2.4,0,0,-2.9,-2.3,0.8,1.2,0,-6.5,-7.5,-24.8,-25.4,0,0,-3.8,-2.6,-2.4,-3,0,-6.4,-5.5,0,-5.4,-3.6,-0.9,-1.4,0,0,0,3.4,-8.7,-11.6,-8,-9.4,0,0,-6.5,-7,0,1.3,1.7,0,0,0,0,9,10.1,0,0,0,0,18.8,12.3,0,0,-6.4,-5.9,0,0,0,0,6.2,6.1,0,0,5.6,11.2,4.9,5.2,0,0,23,18.1,0,0,0,15,20.6,0,0,0,0,0,-13.5,-13.1,0,-3.3,-5,0,0.7,0.8,0,0,6,5.9,0,0,-1.9,-1.8,-5,-5.8,0,0,-7.5,-14.7,-15.4,-7.2,-6.2,-3.8,-3.1,0,-3.7,-2.6,-1.4,0,0,0,0.6,1.2,4.6,0]","mbtiles":["2\/2\/1","7\/67\/47","11\/1086\/754","11\/1086\/755","11\/1087\/754","11\/1087\/755","15\/17387\/12083","15\/17387\/12084","15\/17393\/12079","15\/17388\/12080","15\/17388\/12081","15\/17388\/12082","15\/17388\/12083","15\/17388\/12084","15\/17389\/12080","15\/17389\/12081","15\/17389\/12082","15\/17389\/12083","15\/17389\/12084","15\/17389\/12085","15\/17390\/12079","15\/17390\/12080","15\/17390\/12081","15\/17390\/12082","15\/17390\/12083","15\/17390\/12084","15\/17390\/12085","15\/17390\/12086","15\/17391\/12079","15\/17391\/12080","15\/17391\/12081","15\/17391\/12082","15\/17391\/12083","15\/17391\/12084","15\/17391\/12085","15\/17391\/12086","15\/17392\/12079","15\/17392\/12080","15\/17392\/12081","15\/17392\/12082","15\/17392\/12083","15\/17392\/12084","15\/17392\/12085","15\/17393\/12080","15\/17393\/12081","15\/17393\/12082","15\/17393\/12083","15\/17393\/12084","15\/17394\/12079","15\/17394\/12080","15\/17394\/12081","15\/17394\/12082","15\/17395\/12080","15\/17395\/12081","15\/17395\/12082"],"geojson_url":"https:\/\/geohub.webmapp.it\/api\/ec\/track\/download\/23.geojson","gpx_url":"https:\/\/geohub.webmapp.it\/api\/ec\/track\/download\/23.gpx","kml_url":"https:\/\/geohub.webmapp.it\/api\/ec\/track\/download\/23.kml","taxonomy":{"activity":[{"id":2,"created_at":"2021-07-05T16:34:23.000000Z","updated_at":"2021-07-19T08:21:01.000000Z","name":{"it":"In bicicletta","en":"In bicicletta"},"description":{"en":null},"excerpt":{"en":null},"identifier":"cycling","stroke_width":"2.5","min_visible_zoom":"5","min_size_zoom":"19","min_size":"1","max_size":"4","icon_zoom":"5","icon_size":"0.1"}],"where":[9,73,4048]},"duration":{"cycling":{"forward":114,"backward":114}},"related_pois":[{"type":"Feature","properties":{"id":20,"name":{"it":"Centro visite Alberese","en":"Alberese Visitors Center"}},"geometry":{"type":"Point","coordinates":[11.1038638138004,42.6690165826627]}},{"type":"Feature","properties":{"id":24,"name":{"it":"Infopoint Proloco Alborensis","en":"Infopoint Proloco Alborensis"}},"geometry":{"type":"Point","coordinates":[11.1053963688474,42.6691096609018]}},{"type":"Feature","properties":{"id":13,"name":{"it":"Casetta dei Pinottolai","en":"Casetta dei Pinottolai"}},"geometry":{"type":"Point","coordinates":[11.0432912309498,42.6632898286994]}},{"type":"Feature","properties":{"id":5,"name":{"it":"Torre di Collelungo","en":"Collelungo Tower"},"feature_image":{"id":99,"name":{"it":"ParcoMaremma-vista-torri.JPG","en":"ParcoMaremma-vista-torri.JPG"},"url":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/99.jpg","api_url":"https:\/\/geohub.webmapp.it\/api\/ec\/media\/99","sizes":{"108x148":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x148\/99_108x148.jpg","108x137":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x137\/99_108x137.jpg","150x150":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/150x150\/99_150x150.jpg","225x100":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/225x100\/99_225x100.jpg","118x138":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x138\/99_118x138.jpg","108x139":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x139\/99_108x139.jpg","118x117":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x117\/99_118x117.jpg","335x250":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/335x250\/99_335x250.jpg","400x200":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/400x200\/99_400x200.jpg","1440x500":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1440x500\/99_1440x500.jpg","1920x":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1920x\/99_1920x.jpg"}}},"geometry":{"type":"Point","coordinates":[11.0687756286342,42.6394306884687]}},{"type":"Feature","properties":{"id":27,"name":{"it":"Spiaggia di Collelungo","en":null},"feature_image":{"id":88,"name":{"it":"ParcoMaremma-spiaggia-colle-lungo-e-vista-torri.JPG","en":"ParcoMaremma-spiaggia-colle-lungo-e-vista-torri.JPG"},"url":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/88.jpg","api_url":"https:\/\/geohub.webmapp.it\/api\/ec\/media\/88","sizes":{"108x148":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x148\/88_108x148.jpg","108x137":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x137\/88_108x137.jpg","150x150":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/150x150\/88_150x150.jpg","225x100":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/225x100\/88_225x100.jpg","118x138":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x138\/88_118x138.jpg","108x139":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x139\/88_108x139.jpg","118x117":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x117\/88_118x117.jpg","335x250":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/335x250\/88_335x250.jpg","400x200":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/400x200\/88_400x200.jpg","1440x500":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1440x500\/88_1440x500.jpg","1920x":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1920x\/88_1920x.jpg"}}},"geometry":{"type":"Point","coordinates":[11.0675116860241,42.6369020079618]}}]},"geometry":{"type":"LineString","coordinates":[[11.103672,42.669571,20,0],[11.103643,42.669595,20,-4.8],[11.103397,42.669887,18,-1.2],[11.101377,42.672144,16,-0.6],[11.101284,42.672257,16,0],[11.101234,42.672335,16,0],[11.101198,42.672374,16,-12],[11.101105,42.672449,14,-10.6],[11.101054,42.672506,14,0],[11.10087,42.672717,14,-0.6],[11.0954,42.678764,9,-0.5],[11.094445,42.679845,9,0],[11.094232,42.680035,9,0],[11.094114,42.6801,9,0],[11.094,42.680141,9,0],[11.093831,42.680192,9,-1.3],[11.093132,42.680364,8,-1.1],[11.092788,42.680451,8,-1],[11.09093,42.680924,6,-1.1],[11.090629,42.681005,6,0],[11.09054,42.681038,6,0],[11.090477,42.681066,6,0],[11.090418,42.681111,6,0],[11.09038,42.681169,6,1.2],[11.090026,42.681821,7,1.2],[11.089978,42.681859,7,0],[11.089912,42.681875,7,0],[11.089836,42.681875,7,0],[11.088902,42.681612,7,-1.2],[11.087021,42.681069,4,-1.7],[11.086926,42.68102,4,0],[11.086849,42.680965,4,0],[11.086702,42.680761,4,0],[11.086664,42.680743,4,0],[11.086618,42.680735,4,0],[11.086556,42.680742,4,-1.1],[11.085548,42.680968,3,-0.4],[11.083446,42.681424,3,0],[11.082421,42.681647,3,-0.9],[11.082193,42.681716,2,-3.4],[11.082098,42.681765,2,0],[11.082065,42.681815,2,0],[11.082047,42.681843,2,0],[11.08203,42.681879,2,0],[11.082021,42.681899,2,0],[11.081993,42.681915,2,0],[11.081957,42.681919,2,0],[11.080336,42.681481,2,0],[11.080184,42.681452,2,0],[11.079941,42.681418,2,0],[11.07838,42.680993,2,-0.4],[11.07698,42.680613,1,-0.8],[11.076943,42.680592,1,0],[11.076925,42.68056,1,0],[11.076956,42.680494,1,0],[11.076972,42.680461,1,2],[11.076402,42.680303,2,1.3],[11.075163,42.679959,3,2.5],[11.075012,42.679917,5,8.7],[11.074891,42.679917,5,0],[11.074718,42.679885,5,0],[11.074299,42.679783,5,0],[11.074191,42.679757,5,0],[11.074065,42.67974,5,-11.4],[11.07398,42.679742,3,-14.7],[11.073907,42.679773,3,0],[11.073841,42.679874,3,-1],[11.073446,42.680638,2,-0.9],[11.073382,42.680763,2,0],[11.073293,42.680873,2,0],[11.073221,42.680928,2,0],[11.072934,42.681081,2,0],[11.072355,42.681376,2,0],[11.07231,42.681384,2,0],[11.072278,42.681377,2,0],[11.072241,42.681358,2,0],[11.072037,42.681093,2,0],[11.07172,42.680768,2,0],[11.071493,42.680559,2,0],[11.071424,42.680505,2,0],[11.070857,42.680059,2,0],[11.070457,42.679793,2,0],[11.070111,42.679577,2,0],[11.069392,42.679179,2,0],[11.068887,42.678934,2,0],[11.068551,42.67879,2,0],[11.068089,42.678624,2,0],[11.064718,42.677699,2,-0.2],[11.062261,42.677034,1,0],[11.060003,42.676423,2,0.3],[11.058097,42.675967,2,0],[11.053155,42.674747,2,0],[11.0528,42.674685,2,0],[11.052308,42.674636,2,0],[11.050699,42.674574,2,0],[11.04961,42.674501,2,-0.8],[11.049143,42.67446,1,-1.7],[11.048918,42.674431,1,0],[11.04869,42.674396,1,0],[11.048416,42.674341,1,0],[11.048224,42.67429,1,0],[11.048026,42.674212,1,0],[11.047837,42.674108,1,0],[11.047771,42.67405,1,0],[11.047742,42.674014,1,0],[11.04774,42.67397,1,0],[11.047889,42.673736,1,0],[11.04828,42.673128,1,1.3],[11.048711,42.672461,3,2.3],[11.048723,42.672398,3,0],[11.048718,42.672346,3,0],[11.048723,42.672294,3,0],[11.048755,42.672219,3,0],[11.048764,42.672154,3,0],[11.048755,42.672109,3,7.5],[11.048723,42.672038,4,5.1],[11.048669,42.671946,4,-1.6],[11.048094,42.670975,2,-1.1],[11.047394,42.669771,1,-0.6],[11.047344,42.669691,1,0],[11.04729,42.669623,1,0],[11.047157,42.669487,1,0],[11.047149,42.669454,1,0],[11.047158,42.669427,1,0],[11.047179,42.669408,1,0],[11.047236,42.669386,1,11.4],[11.047273,42.669372,2,11.6],[11.047332,42.669356,2,0],[11.047392,42.669333,2,0],[11.047411,42.669313,2,0],[11.047419,42.669286,2,0],[11.047415,42.669263,2,0],[11.047371,42.669197,2,0],[11.047234,42.669059,2,0],[11.047195,42.669006,2,-1.6],[11.046926,42.668546,1,-0.4],[11.045999,42.666963,1,0],[11.045983,42.666891,1,0],[11.046036,42.666756,1,0],[11.046044,42.666722,1,0],[11.046037,42.666685,1,13.5],[11.046022,42.666658,2,11.5],[11.045997,42.666613,2,0],[11.045958,42.666542,2,1.2],[11.045605,42.665889,3,1.1],[11.04553,42.665786,3,6.5],[11.045415,42.665649,5,3.5],[11.045206,42.665331,5,1],[11.04492,42.664806,6,1.3],[11.044845,42.664687,6,0],[11.044788,42.664558,6,0],[11.044737,42.66443,6,0],[11.044708,42.664397,6,0],[11.04468,42.664367,6,0],[11.04463,42.664287,6,0],[11.044531,42.664035,6,2.7],[11.044496,42.663972,7,4.5],[11.044406,42.66386,7,0],[11.0443,42.663709,7,0],[11.044116,42.663406,7,0],[11.044004,42.663188,7,2.1],[11.043899,42.663009,8,2.4],[11.0438,42.662836,8,0],[11.043636,42.662573,8,0],[11.043436,42.662241,8,-2.1],[11.043406,42.662187,7,-2.2],[11.043002,42.661473,6,-0.9],[11.042865,42.66125,6,0],[11.042762,42.661101,6,0],[11.042692,42.660972,6,0],[11.042537,42.660638,6,0],[11.042496,42.660569,6,0],[11.042409,42.660462,6,-2.9],[11.042297,42.6603,5,-3.5],[11.042255,42.660229,5,0],[11.042235,42.660153,5,0],[11.042178,42.660048,5,0],[11.041992,42.659779,5,0],[11.041871,42.65957,5,0],[11.041802,42.659424,5,0],[11.041693,42.659276,5,0],[11.041533,42.659009,5,0],[11.041511,42.658958,5,0],[11.041496,42.658923,5,0],[11.041248,42.658518,5,0],[11.04123,42.658474,5,0],[11.041221,42.658421,5,0],[11.041191,42.658354,5,0],[11.041096,42.658235,5,0],[11.041029,42.658135,5,0],[11.040965,42.657992,5,0],[11.04094,42.657936,5,0],[11.040743,42.657524,5,0],[11.04066,42.657401,5,0],[11.040625,42.657327,5,0],[11.04057,42.65724,5,0],[11.040509,42.657194,5,0],[11.040444,42.657121,5,0],[11.040397,42.657031,5,0],[11.040205,42.656715,5,0],[11.040125,42.656475,5,0],[11.039967,42.656243,5,0],[11.039881,42.656075,5,0],[11.039763,42.655868,5,0],[11.039354,42.655196,5,0],[11.039292,42.65502,5,0],[11.039227,42.654954,5,0],[11.039185,42.654928,5,0],[11.039153,42.654887,5,0],[11.039022,42.654655,5,0],[11.038921,42.654413,5,0],[11.038886,42.654351,5,0],[11.038882,42.654342,5,0],[11.038753,42.654182,5,0],[11.038737,42.654133,5,8],[11.03873,42.654071,6,4.3],[11.038655,42.653931,6,0],[11.038577,42.653823,6,0],[11.038478,42.653671,6,0],[11.03846,42.653642,6,0],[11.038454,42.653633,6,0],[11.038397,42.653605,6,5.4],[11.038249,42.65356,7,4.4],[11.038153,42.653508,7,0],[11.038111,42.653475,7,0],[11.037723,42.652783,7,-0.4],[11.037024,42.651589,6,-0.7],[11.037004,42.651559,6,0],[11.037139,42.651516,6,-2.8],[11.03736,42.65138,5,-1.6],[11.03774,42.651192,5,0],[11.037902,42.651112,5,0],[11.038005,42.65133,5,0],[11.038122,42.651313,5,0],[11.038438,42.65124,5,0],[11.038954,42.651151,5,0],[11.039259,42.651125,5,0],[11.039767,42.651084,5,0],[11.040451,42.651011,5,-1.2],[11.0408,42.65095,4,-1.4],[11.04134,42.650933,4,1],[11.041955,42.650827,5,0.9],[11.042623,42.650693,5,0],[11.043186,42.650609,5,1],[11.043717,42.650296,6,1.2],[11.044112,42.650296,6,-1.6],[11.044477,42.650346,5,-1.5],[11.044918,42.650285,5,0],[11.045495,42.650101,5,0],[11.046452,42.650017,5,0],[11.046832,42.649726,5,1],[11.047417,42.649475,6,0],[11.048093,42.649346,5,-0.9],[11.048663,42.649179,5,0],[11.049248,42.649167,5,0],[11.049848,42.649084,5,0],[11.050236,42.648916,5,0],[11.051368,42.648704,5,1],[11.052424,42.648134,7,0.6],[11.052955,42.647938,6,-1.1],[11.053502,42.647882,6,-1],[11.054163,42.647865,5,0],[11.055394,42.647592,6,0.6],[11.055964,42.64743,6,0],[11.056875,42.64696,6,0],[11.057496,42.646783,6,0],[11.05803,42.646631,6,-0.5],[11.059504,42.64601,5,-0.4],[11.061023,42.645664,5,0],[11.061639,42.645602,5,0],[11.062391,42.646105,5,1.1],[11.062454,42.646118,6,2.8],[11.062809,42.646195,6,0],[11.063087,42.64616,6,0],[11.06334,42.646128,6,6.9],[11.063667,42.645859,10,2.4],[11.063903,42.645491,8,-2.2],[11.064328,42.645211,8,0],[11.064753,42.645032,8,4.2],[11.064981,42.644775,11,0],[11.06527,42.643708,8,-2.1],[11.06606,42.642814,6,-1.3],[11.066204,42.642451,6,0],[11.066189,42.642104,6,0],[11.066311,42.641819,6,0],[11.06666,42.641573,6,0],[11.06682,42.641216,6,0],[11.066829,42.640942,6,-0.8],[11.066861,42.640086,5,-0.9],[11.066865,42.639981,5,0],[11.066987,42.639534,5,5.4],[11.06722,42.639199,10,8.9],[11.067398,42.639521,8,-3.7],[11.067401,42.639672,8,0],[11.067459,42.639899,8,0],[11.067398,42.639979,8,0],[11.067401,42.640191,8,-4.1],[11.067418,42.640418,6,-3],[11.067521,42.640793,6,0],[11.067668,42.640921,6,0],[11.067713,42.64104,6,0],[11.067765,42.641024,6,0],[11.067755,42.640977,6,0],[11.067773,42.640932,6,0],[11.067815,42.640897,6,0],[11.067874,42.640879,6,0],[11.067937,42.640882,6,0],[11.067993,42.640904,6,0],[11.068031,42.640942,6,1.3],[11.068897,42.640747,7,0.7],[11.069584,42.640574,7,0],[11.070153,42.640432,7,0],[11.070344,42.640421,7,0],[11.07046,42.640444,7,0],[11.070587,42.640491,7,0],[11.070733,42.640565,7,15.7],[11.070881,42.640673,12,8.8],[11.071177,42.640956,12,0],[11.071225,42.640995,12,0],[11.071341,42.641092,12,-22],[11.071534,42.641218,4,-14.2],[11.071783,42.641473,4,5.9],[11.072419,42.642254,12,6.9],[11.072525,42.642363,12,0],[11.072634,42.642449,12,0],[11.072835,42.64259,12,12.4],[11.07378,42.643226,28,9.1],[11.074401,42.643664,28,0],[11.074404,42.643667,28,42],[11.074782,42.643987,50,37.6],[11.074832,42.644038,50,0],[11.074875,42.644144,50,0],[11.074899,42.644244,50,0],[11.074875,42.644527,50,9.6],[11.074825,42.644802,56,6.1],[11.07475,42.645409,56,0],[11.074716,42.645569,56,14],[11.074727,42.645727,61,16.4],[11.074766,42.645838,61,0],[11.074829,42.645911,61,0],[11.074889,42.645951,61,0],[11.074967,42.645989,61,0],[11.075104,42.646016,61,0],[11.075243,42.646013,61,0],[11.07539,42.645982,61,0],[11.075575,42.645929,61,16.1],[11.075884,42.645853,68,18.2],[11.076017,42.645829,68,0],[11.076166,42.645845,68,0],[11.076271,42.645882,68,0],[11.076361,42.645951,68,0],[11.07642,42.646024,68,0],[11.076452,42.646125,68,0],[11.07644,42.646206,68,0],[11.076406,42.646292,68,0],[11.076358,42.646344,68,0],[11.076291,42.646385,68,36.5],[11.075935,42.646538,84,6],[11.075777,42.646622,71,-33.3],[11.075572,42.646732,71,0],[11.075474,42.646802,71,0],[11.075416,42.646881,71,0],[11.075379,42.646978,71,0],[11.075369,42.647077,71,0],[11.075348,42.64716,71,0],[11.075306,42.647219,71,0],[11.075257,42.647269,71,0],[11.075174,42.64731,71,0],[11.075023,42.647341,71,0],[11.074897,42.647333,71,0],[11.074763,42.647337,71,-39.5],[11.074618,42.647371,61,-31.2],[11.07453,42.647422,64,15.9],[11.074451,42.647484,64,0],[11.074421,42.647534,64,0],[11.074388,42.647633,64,0],[11.074321,42.648043,64,-5.4],[11.074231,42.648456,59,-6.1],[11.074183,42.648775,59,0],[11.074189,42.649156,59,-4.5],[11.074235,42.649579,55,-5.4],[11.074294,42.649821,55,0],[11.074368,42.649999,55,-2.2],[11.074503,42.650198,54,-2.3],[11.074605,42.650346,54,24.3],[11.074763,42.650544,65,20.9],[11.074959,42.650728,65,0],[11.075092,42.650835,65,0],[11.075192,42.65091,65,6],[11.075542,42.651134,68,5.5],[11.07571,42.651219,68,0],[11.075871,42.651271,68,47],[11.07599,42.651293,81,56.4],[11.076099,42.651302,81,0],[11.076349,42.651304,81,0],[11.07672,42.65132,81,0],[11.076837,42.651356,81,0],[11.076897,42.651395,81,0],[11.076952,42.651451,81,0],[11.076989,42.651502,81,0],[11.077011,42.651589,81,0],[11.077007,42.651649,81,0],[11.076984,42.651716,81,0],[11.07691,42.651841,81,15.3],[11.076657,42.652112,89,8.8],[11.076265,42.652503,89,2],[11.07595,42.652853,91,-7.1],[11.075758,42.653159,83,-12.1],[11.075666,42.653403,83,0],[11.075627,42.653538,83,0],[11.075604,42.653707,83,-3.5],[11.075601,42.654052,81,-4.7],[11.075598,42.654089,81,-2.9],[11.075565,42.654674,79,-2.4],[11.075514,42.654846,79,0],[11.075399,42.655148,79,0],[11.075269,42.655453,79,-2.9],[11.075164,42.655743,77,-2.3],[11.074891,42.656189,77,0.8],[11.074458,42.656691,78,1.2],[11.074344,42.656812,78,0],[11.074061,42.657142,78,-6.5],[11.073351,42.657984,68,-7.5],[11.073161,42.658141,68,-24.8],[11.072988,42.65831,56,-25.4],[11.072872,42.658493,56,0],[11.072831,42.658641,56,0],[11.072797,42.658882,56,-3.8],[11.072874,42.659344,53,-2.6],[11.072949,42.659931,53,-2.4],[11.07298,42.660089,51,-3],[11.073065,42.66053,51,0],[11.07306,42.660733,51,-6.4],[11.072997,42.661092,47,-5.5],[11.072937,42.661378,47,0],[11.072888,42.66169,47,-5.4],[11.072858,42.662036,43,-3.6],[11.072933,42.662686,43,-0.9],[11.072986,42.663048,42,-1.4],[11.072995,42.663335,42,0],[11.072927,42.663918,42,0],[11.072886,42.664129,42,0],[11.072812,42.664355,42,3.4],[11.072678,42.664628,44,-8.7],[11.072289,42.665316,32,-11.6],[11.072243,42.665499,32,-8],[11.072218,42.665647,29,-9.4],[11.072217,42.665784,29,0],[11.072241,42.665925,29,0],[11.072259,42.66615,29,-6.5],[11.072305,42.666474,25,-7],[11.072314,42.666665,25,0],[11.072352,42.666964,25,1.3],[11.072377,42.667349,26,1.7],[11.072361,42.667497,26,0],[11.072318,42.667647,26,0],[11.072219,42.667871,26,0],[11.072175,42.668017,26,0],[11.072149,42.668147,26,9],[11.072172,42.668316,29,10.1],[11.072207,42.66841,29,0],[11.072218,42.66844,29,0],[11.072266,42.668517,29,0],[11.072377,42.668616,29,0],[11.072533,42.668682,29,18.8],[11.072667,42.668713,34,12.3],[11.073025,42.668698,34,0],[11.073434,42.668658,34,0],[11.073617,42.66862,34,-6.4],[11.073792,42.668563,32,-5.9],[11.073985,42.668478,32,0],[11.074356,42.66829,32,0],[11.074566,42.66824,32,0],[11.074703,42.668238,32,0],[11.074857,42.668276,32,6.2],[11.075068,42.668342,34,6.1],[11.075219,42.668398,34,0],[11.075339,42.668442,34,0],[11.076112,42.66868,34,5.6],[11.076361,42.668706,39,11.2],[11.07665,42.668716,39,4.9],[11.078103,42.668718,46,5.2],[11.078293,42.668735,46,0],[11.078463,42.668798,46,0],[11.078564,42.668873,46,23],[11.078784,42.669144,57,18.1],[11.078925,42.66934,57,0],[11.078993,42.66949,57,0],[11.079033,42.66963,57,0],[11.079064,42.669891,57,15],[11.079121,42.670099,65,20.6],[11.079173,42.670223,65,0],[11.079257,42.670357,65,0],[11.079365,42.670467,65,0],[11.079524,42.670576,65,0],[11.079677,42.670646,65,0],[11.079858,42.670703,65,-13.5],[11.08011,42.670733,60,-13.1],[11.080319,42.670728,60,0],[11.081079,42.67068,60,-3.3],[11.081415,42.670659,57,-5],[11.081809,42.670671,57,0],[11.082342,42.670699,57,0.7],[11.0836,42.670774,58,0.8],[11.083856,42.670784,58,0],[11.084095,42.670777,58,0],[11.084582,42.670717,58,6],[11.086093,42.670509,68,5.9],[11.086621,42.670442,68,0],[11.086887,42.670385,68,0],[11.087076,42.670323,68,-1.9],[11.089121,42.669497,64,-1.8],[11.089486,42.66939,64,-5],[11.089812,42.669331,61,-5.8],[11.090106,42.669299,61,0],[11.090446,42.669302,61,0],[11.090783,42.669338,61,-7.5],[11.091564,42.669461,54,-14.7],[11.09206,42.669554,45,-15.4],[11.092246,42.669588,45,-7.2],[11.094027,42.669897,33,-6.2],[11.095325,42.670102,29,-3.8],[11.095902,42.670211,27,-3.1],[11.096087,42.670261,27,0],[11.096285,42.670349,27,-3.7],[11.098119,42.671105,20,-2.6],[11.100736,42.672184,16,-1.4],[11.101198,42.672374,16,0],[11.101234,42.672335,16,0],[11.101284,42.672257,16,0],[11.101377,42.672144,16,0.6],[11.103397,42.669887,18,1.2],[11.103643,42.669595,20,4.6],[11.10369,42.669557,20,0]]}}', true);
        $srcPath = 'src';
        $destPath = 'dest';

        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('runElevationChartImageGeneration')
                ->once()
                ->andReturn();
        });

        Storage::extend('mock', function () {
            return \Mockery::mock(Filesystem::class);
        });

        Config::set('filesystems.disks.local', ['driver' => 'mock']);
        Config::set('filesystems.disks.s3', ['driver' => 'mock']);
        $localDisk = Storage::disk('local');
        $ecMediaDisk = Storage::disk('s3');

        Storage::shouldReceive('disk')
            ->with('local')
            ->once()
            ->andReturn(
                $localDisk
            );
        $localDisk->shouldReceive('exists')
            ->with('elevation_charts')
            ->once()
            ->andReturn(true);
        $localDisk->shouldReceive('exists')
            ->with('geojson')
            ->once()
            ->andReturn(true);
        $localDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);
        $localDisk->shouldReceive('path')
            ->with('geojson/23.geojson')
            ->once()
            ->andReturn($srcPath);
        $localDisk->shouldReceive('path')
            ->with('elevation_charts/23.svg')
            ->once()
            ->andReturn($destPath);
        $localDisk->shouldReceive('delete')
            ->with('geojson/23.geojson')
            ->once()
            ->andReturn(true);
        $localDisk->shouldReceive('readStream')
            ->with('elevation_charts/23.svg')
            ->once()
            ->andReturn(true);

        Storage::shouldReceive('disk')
            ->with('s3')
            ->once()
            ->andReturn($ecMediaDisk);
        $ecMediaDisk->shouldReceive('exists')
            ->with('ectrack/elevation_charts/23.svg')
            ->once()
            ->andReturn(false);

        $ecMediaDisk->shouldReceive('writeStream')
            ->once()
            ->andReturn(true);

        $ecMediaDisk->shouldReceive('exists')
            ->with('ectrack/elevation_charts/23_old.svg')
            ->once()
            ->andReturn(false);
        $path = 'testPath';
        $ecMediaDisk->shouldReceive('path')
            ->with('ectrack/elevation_charts/23.svg')
            ->once()
            ->andReturn($path);

        $result = $ecTrackService->generateElevationChartImage($ecTrack);
        $this->assertEquals($path, $result);
    }

    public function test_elevation_chart_image_on_job_completion() {
        $trackId = 23;
        $ecTrack = json_decode('{"type":"Feature","properties":{"id":' . $trackId . ',"created_at":"2021-07-07T15:05:55.000000Z","updated_at":"2021-09-28T12:57:35.000000Z","name":{"it":"Percorso ciclabile per Collelungo \u2013 Pinastrellaia","en":"Cycle path to Collelungo \u2013 Pinastrellaia"},"description":{"it":"<p>L&#39;itinerario pu&ograve; essere svolto da Alberese fino alla loc. di Collelungo (percorrendo la Strada degli Ulivi da Vergheria fino a Collelungo), oppure percorrendo la ciclabile fino a Marina di Alberese e successivamente, attraverso la strada della Pinastrellaia, si raggiunge la spiaggia di Collelungo.<\/p>\n\n<p>Prima di raggiungere la spiaggia &egrave; presente una zona di sosta con rastrelliere dove &egrave; possibile lasciare le biciclette.<\/p>\n\n<p><strong>Massimo 150 partecipanti.<\/strong><\/p>\n\n<p><em><strong>Nei gruppi con almeno 20 persone la guida &egrave; obbligatoria sempre.<\/strong><\/em><\/p>\n\n<h4>Nel percorre gli itinerari in bici si raccomanda la cautela, in quanto si sta visitando un&#39;area protetta ed &egrave; presente la possibilit&agrave; di incontrare fruitori di altre modalit&agrave; di visita (a cavallo, a piedi).<\/h4>\n\n<p>&Egrave; possibile noleggiare le biciclette nei pressi del punto di partenza.<br \/>\n<br \/>\n<strong>NB: E&#39; fortemente sconsigliato il&nbsp;transito nel percorso ciclabile&nbsp;Collelungo-Pinastrellaia (Strada degli Ulivi) con il trasportino per bambini&nbsp;a seguito ( carrellino), in quanto i varchi presenti lungo il percorso ostacolano&nbsp;il passaggio di tali mezzi.<\/strong><\/p>","en":"<p>Scheduled departure from the Alberese Visitor Center, take the road of Olives from Vergheria to the Collelungo roundabout. From here you can continue to the beach, where there are racks, where you can leave your bicycles. Subsequently, Marina di Alberese can be reached via the Pinastrellaia road; return to Alberese using the cycle path. You enter the heart of the park up to Collelungo beach along the Strada degli Olivi, then cross the Pineta Granducale pinewood along the Pinastrellaia road until you reach Marina di Alberese. From here you return to the Visitor Center along the cycle path.<\/p>\n\n<p><strong>Maximum 150 participants.<\/strong><\/p>\n\n<p><strong>Transit along the Collelungo-Pinastrellaia cycle path (Strada degli Ulivi) is strongly discouraged with a child carrier (trolley), as the gates along the route make it difficult for such vehicles to pass.<\/strong><\/p>"},"excerpt":{"it":"Litinerario pu\u00f2 essere svolto da Alberese fino alla loc. di Collelungo (percorrendo la Strada degli Ulivi da Vergheria fino a Collelungo), oppure percorrendo la ciclabile fino a Marina di Alberese e successivamente, attraverso la strada della Pinastrell","en":"Scheduled departure from the Alberese Visitor Center, take the road of Olives from Vergheria to the Collelungo roundabout. From here you can continue to the beach, where there are racks, where you can leave your bicycles."},"distance_comp":19,"user_id":11,"feature_image":{"id":108,"name":{"it":"Parco Maremma - Ciclabile Collelungo - Cover","en":"Parco Maremma - Ciclabile Collelungo - Cover"},"url":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/108.jpg","api_url":"https:\/\/geohub.webmapp.it\/api\/ec\/media\/108","sizes":{"108x148":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x148\/108_108x148.jpg","108x137":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x137\/108_108x137.jpg","150x150":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/150x150\/108_150x150.jpg","225x100":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/225x100\/108_225x100.jpg","118x138":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x138\/108_118x138.jpg","108x139":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x139\/108_108x139.jpg","118x117":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x117\/108_118x117.jpg","335x250":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/335x250\/108_335x250.jpg","400x200":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/400x200\/108_400x200.jpg","1440x500":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1440x500\/108_1440x500.jpg","1920x":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1920x\/108_1920x.jpg"}},"distance":19,"ascent":224,"descent":224,"ele_from":20,"ele_to":20,"ele_min":1,"ele_max":91,"duration_forward":365,"duration_backward":365,"difficulty":{"it":"Media","en":"Medium"},"slope":"[0,-4.8,-1.2,-0.6,0,0,-12,-10.6,0,-0.6,-0.5,0,0,0,0,-1.3,-1.1,-1,-1.1,0,0,0,0,1.2,1.2,0,0,0,-1.2,-1.7,0,0,0,0,0,-1.1,-0.4,0,-0.9,-3.4,0,0,0,0,0,0,0,0,0,0,-0.4,-0.8,0,0,0,2,1.3,2.5,8.7,0,0,0,0,-11.4,-14.7,0,-1,-0.9,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,-0.2,0,0.3,0,0,0,0,0,-0.8,-1.7,0,0,0,0,0,0,0,0,0,0,1.3,2.3,0,0,0,0,0,7.5,5.1,-1.6,-1.1,-0.6,0,0,0,0,0,0,11.4,11.6,0,0,0,0,0,0,0,-1.6,-0.4,0,0,0,0,13.5,11.5,0,1.2,1.1,6.5,3.5,1,1.3,0,0,0,0,0,0,2.7,4.5,0,0,0,2.1,2.4,0,0,-2.1,-2.2,-0.9,0,0,0,0,0,-2.9,-3.5,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,8,4.3,0,0,0,0,0,5.4,4.4,0,0,-0.4,-0.7,0,-2.8,-1.6,0,0,0,0,0,0,0,0,-1.2,-1.4,1,0.9,0,1,1.2,-1.6,-1.5,0,0,0,1,0,-0.9,0,0,0,0,1,0.6,-1.1,-1,0,0.6,0,0,0,-0.5,-0.4,0,0,1.1,2.8,0,0,6.9,2.4,-2.2,0,4.2,0,-2.1,-1.3,0,0,0,0,0,-0.8,-0.9,0,5.4,8.9,-3.7,0,0,0,-4.1,-3,0,0,0,0,0,0,0,0,0,0,1.3,0.7,0,0,0,0,0,15.7,8.8,0,0,-22,-14.2,5.9,6.9,0,0,12.4,9.1,0,42,37.6,0,0,0,9.6,6.1,0,14,16.4,0,0,0,0,0,0,0,16.1,18.2,0,0,0,0,0,0,0,0,0,36.5,6,-33.3,0,0,0,0,0,0,0,0,0,0,0,-39.5,-31.2,15.9,0,0,0,-5.4,-6.1,0,-4.5,-5.4,0,-2.2,-2.3,24.3,20.9,0,0,6,5.5,0,47,56.4,0,0,0,0,0,0,0,0,0,0,15.3,8.8,2,-7.1,-12.1,0,0,-3.5,-4.7,-2.9,-2.4,0,0,-2.9,-2.3,0.8,1.2,0,-6.5,-7.5,-24.8,-25.4,0,0,-3.8,-2.6,-2.4,-3,0,-6.4,-5.5,0,-5.4,-3.6,-0.9,-1.4,0,0,0,3.4,-8.7,-11.6,-8,-9.4,0,0,-6.5,-7,0,1.3,1.7,0,0,0,0,9,10.1,0,0,0,0,18.8,12.3,0,0,-6.4,-5.9,0,0,0,0,6.2,6.1,0,0,5.6,11.2,4.9,5.2,0,0,23,18.1,0,0,0,15,20.6,0,0,0,0,0,-13.5,-13.1,0,-3.3,-5,0,0.7,0.8,0,0,6,5.9,0,0,-1.9,-1.8,-5,-5.8,0,0,-7.5,-14.7,-15.4,-7.2,-6.2,-3.8,-3.1,0,-3.7,-2.6,-1.4,0,0,0,0.6,1.2,4.6,0]","mbtiles":["2\/2\/1","7\/67\/47","11\/1086\/754","11\/1086\/755","11\/1087\/754","11\/1087\/755","15\/17387\/12083","15\/17387\/12084","15\/17393\/12079","15\/17388\/12080","15\/17388\/12081","15\/17388\/12082","15\/17388\/12083","15\/17388\/12084","15\/17389\/12080","15\/17389\/12081","15\/17389\/12082","15\/17389\/12083","15\/17389\/12084","15\/17389\/12085","15\/17390\/12079","15\/17390\/12080","15\/17390\/12081","15\/17390\/12082","15\/17390\/12083","15\/17390\/12084","15\/17390\/12085","15\/17390\/12086","15\/17391\/12079","15\/17391\/12080","15\/17391\/12081","15\/17391\/12082","15\/17391\/12083","15\/17391\/12084","15\/17391\/12085","15\/17391\/12086","15\/17392\/12079","15\/17392\/12080","15\/17392\/12081","15\/17392\/12082","15\/17392\/12083","15\/17392\/12084","15\/17392\/12085","15\/17393\/12080","15\/17393\/12081","15\/17393\/12082","15\/17393\/12083","15\/17393\/12084","15\/17394\/12079","15\/17394\/12080","15\/17394\/12081","15\/17394\/12082","15\/17395\/12080","15\/17395\/12081","15\/17395\/12082"],"geojson_url":"https:\/\/geohub.webmapp.it\/api\/ec\/track\/download\/23.geojson","gpx_url":"https:\/\/geohub.webmapp.it\/api\/ec\/track\/download\/23.gpx","kml_url":"https:\/\/geohub.webmapp.it\/api\/ec\/track\/download\/23.kml","taxonomy":{"activity":[{"id":2,"created_at":"2021-07-05T16:34:23.000000Z","updated_at":"2021-07-19T08:21:01.000000Z","name":{"it":"In bicicletta","en":"In bicicletta"},"description":{"en":null},"excerpt":{"en":null},"identifier":"cycling","stroke_width":"2.5","min_visible_zoom":"5","min_size_zoom":"19","min_size":"1","max_size":"4","icon_zoom":"5","icon_size":"0.1"}],"where":[9,73,4048]},"duration":{"cycling":{"forward":114,"backward":114}},"related_pois":[{"type":"Feature","properties":{"id":20,"name":{"it":"Centro visite Alberese","en":"Alberese Visitors Center"}},"geometry":{"type":"Point","coordinates":[11.1038638138004,42.6690165826627]}},{"type":"Feature","properties":{"id":24,"name":{"it":"Infopoint Proloco Alborensis","en":"Infopoint Proloco Alborensis"}},"geometry":{"type":"Point","coordinates":[11.1053963688474,42.6691096609018]}},{"type":"Feature","properties":{"id":13,"name":{"it":"Casetta dei Pinottolai","en":"Casetta dei Pinottolai"}},"geometry":{"type":"Point","coordinates":[11.0432912309498,42.6632898286994]}},{"type":"Feature","properties":{"id":5,"name":{"it":"Torre di Collelungo","en":"Collelungo Tower"},"feature_image":{"id":99,"name":{"it":"ParcoMaremma-vista-torri.JPG","en":"ParcoMaremma-vista-torri.JPG"},"url":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/99.jpg","api_url":"https:\/\/geohub.webmapp.it\/api\/ec\/media\/99","sizes":{"108x148":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x148\/99_108x148.jpg","108x137":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x137\/99_108x137.jpg","150x150":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/150x150\/99_150x150.jpg","225x100":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/225x100\/99_225x100.jpg","118x138":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x138\/99_118x138.jpg","108x139":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x139\/99_108x139.jpg","118x117":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x117\/99_118x117.jpg","335x250":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/335x250\/99_335x250.jpg","400x200":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/400x200\/99_400x200.jpg","1440x500":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1440x500\/99_1440x500.jpg","1920x":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1920x\/99_1920x.jpg"}}},"geometry":{"type":"Point","coordinates":[11.0687756286342,42.6394306884687]}},{"type":"Feature","properties":{"id":27,"name":{"it":"Spiaggia di Collelungo","en":null},"feature_image":{"id":88,"name":{"it":"ParcoMaremma-spiaggia-colle-lungo-e-vista-torri.JPG","en":"ParcoMaremma-spiaggia-colle-lungo-e-vista-torri.JPG"},"url":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/88.jpg","api_url":"https:\/\/geohub.webmapp.it\/api\/ec\/media\/88","sizes":{"108x148":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x148\/88_108x148.jpg","108x137":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x137\/88_108x137.jpg","150x150":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/150x150\/88_150x150.jpg","225x100":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/225x100\/88_225x100.jpg","118x138":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x138\/88_118x138.jpg","108x139":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/108x139\/88_108x139.jpg","118x117":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/118x117\/88_118x117.jpg","335x250":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/335x250\/88_335x250.jpg","400x200":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/400x200\/88_400x200.jpg","1440x500":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1440x500\/88_1440x500.jpg","1920x":"https:\/\/ecmedia.s3.eu-central-1.amazonaws.com\/EcMedia\/Resize\/1920x\/88_1920x.jpg"}}},"geometry":{"type":"Point","coordinates":[11.0675116860241,42.6369020079618]}}]},"geometry":{"type":"LineString","coordinates":[[11.103672,42.669571,20,0],[11.103643,42.669595,20,-4.8],[11.103397,42.669887,18,-1.2],[11.101377,42.672144,16,-0.6],[11.101284,42.672257,16,0],[11.101234,42.672335,16,0],[11.101198,42.672374,16,-12],[11.101105,42.672449,14,-10.6],[11.101054,42.672506,14,0],[11.10087,42.672717,14,-0.6],[11.0954,42.678764,9,-0.5],[11.094445,42.679845,9,0],[11.094232,42.680035,9,0],[11.094114,42.6801,9,0],[11.094,42.680141,9,0],[11.093831,42.680192,9,-1.3],[11.093132,42.680364,8,-1.1],[11.092788,42.680451,8,-1],[11.09093,42.680924,6,-1.1],[11.090629,42.681005,6,0],[11.09054,42.681038,6,0],[11.090477,42.681066,6,0],[11.090418,42.681111,6,0],[11.09038,42.681169,6,1.2],[11.090026,42.681821,7,1.2],[11.089978,42.681859,7,0],[11.089912,42.681875,7,0],[11.089836,42.681875,7,0],[11.088902,42.681612,7,-1.2],[11.087021,42.681069,4,-1.7],[11.086926,42.68102,4,0],[11.086849,42.680965,4,0],[11.086702,42.680761,4,0],[11.086664,42.680743,4,0],[11.086618,42.680735,4,0],[11.086556,42.680742,4,-1.1],[11.085548,42.680968,3,-0.4],[11.083446,42.681424,3,0],[11.082421,42.681647,3,-0.9],[11.082193,42.681716,2,-3.4],[11.082098,42.681765,2,0],[11.082065,42.681815,2,0],[11.082047,42.681843,2,0],[11.08203,42.681879,2,0],[11.082021,42.681899,2,0],[11.081993,42.681915,2,0],[11.081957,42.681919,2,0],[11.080336,42.681481,2,0],[11.080184,42.681452,2,0],[11.079941,42.681418,2,0],[11.07838,42.680993,2,-0.4],[11.07698,42.680613,1,-0.8],[11.076943,42.680592,1,0],[11.076925,42.68056,1,0],[11.076956,42.680494,1,0],[11.076972,42.680461,1,2],[11.076402,42.680303,2,1.3],[11.075163,42.679959,3,2.5],[11.075012,42.679917,5,8.7],[11.074891,42.679917,5,0],[11.074718,42.679885,5,0],[11.074299,42.679783,5,0],[11.074191,42.679757,5,0],[11.074065,42.67974,5,-11.4],[11.07398,42.679742,3,-14.7],[11.073907,42.679773,3,0],[11.073841,42.679874,3,-1],[11.073446,42.680638,2,-0.9],[11.073382,42.680763,2,0],[11.073293,42.680873,2,0],[11.073221,42.680928,2,0],[11.072934,42.681081,2,0],[11.072355,42.681376,2,0],[11.07231,42.681384,2,0],[11.072278,42.681377,2,0],[11.072241,42.681358,2,0],[11.072037,42.681093,2,0],[11.07172,42.680768,2,0],[11.071493,42.680559,2,0],[11.071424,42.680505,2,0],[11.070857,42.680059,2,0],[11.070457,42.679793,2,0],[11.070111,42.679577,2,0],[11.069392,42.679179,2,0],[11.068887,42.678934,2,0],[11.068551,42.67879,2,0],[11.068089,42.678624,2,0],[11.064718,42.677699,2,-0.2],[11.062261,42.677034,1,0],[11.060003,42.676423,2,0.3],[11.058097,42.675967,2,0],[11.053155,42.674747,2,0],[11.0528,42.674685,2,0],[11.052308,42.674636,2,0],[11.050699,42.674574,2,0],[11.04961,42.674501,2,-0.8],[11.049143,42.67446,1,-1.7],[11.048918,42.674431,1,0],[11.04869,42.674396,1,0],[11.048416,42.674341,1,0],[11.048224,42.67429,1,0],[11.048026,42.674212,1,0],[11.047837,42.674108,1,0],[11.047771,42.67405,1,0],[11.047742,42.674014,1,0],[11.04774,42.67397,1,0],[11.047889,42.673736,1,0],[11.04828,42.673128,1,1.3],[11.048711,42.672461,3,2.3],[11.048723,42.672398,3,0],[11.048718,42.672346,3,0],[11.048723,42.672294,3,0],[11.048755,42.672219,3,0],[11.048764,42.672154,3,0],[11.048755,42.672109,3,7.5],[11.048723,42.672038,4,5.1],[11.048669,42.671946,4,-1.6],[11.048094,42.670975,2,-1.1],[11.047394,42.669771,1,-0.6],[11.047344,42.669691,1,0],[11.04729,42.669623,1,0],[11.047157,42.669487,1,0],[11.047149,42.669454,1,0],[11.047158,42.669427,1,0],[11.047179,42.669408,1,0],[11.047236,42.669386,1,11.4],[11.047273,42.669372,2,11.6],[11.047332,42.669356,2,0],[11.047392,42.669333,2,0],[11.047411,42.669313,2,0],[11.047419,42.669286,2,0],[11.047415,42.669263,2,0],[11.047371,42.669197,2,0],[11.047234,42.669059,2,0],[11.047195,42.669006,2,-1.6],[11.046926,42.668546,1,-0.4],[11.045999,42.666963,1,0],[11.045983,42.666891,1,0],[11.046036,42.666756,1,0],[11.046044,42.666722,1,0],[11.046037,42.666685,1,13.5],[11.046022,42.666658,2,11.5],[11.045997,42.666613,2,0],[11.045958,42.666542,2,1.2],[11.045605,42.665889,3,1.1],[11.04553,42.665786,3,6.5],[11.045415,42.665649,5,3.5],[11.045206,42.665331,5,1],[11.04492,42.664806,6,1.3],[11.044845,42.664687,6,0],[11.044788,42.664558,6,0],[11.044737,42.66443,6,0],[11.044708,42.664397,6,0],[11.04468,42.664367,6,0],[11.04463,42.664287,6,0],[11.044531,42.664035,6,2.7],[11.044496,42.663972,7,4.5],[11.044406,42.66386,7,0],[11.0443,42.663709,7,0],[11.044116,42.663406,7,0],[11.044004,42.663188,7,2.1],[11.043899,42.663009,8,2.4],[11.0438,42.662836,8,0],[11.043636,42.662573,8,0],[11.043436,42.662241,8,-2.1],[11.043406,42.662187,7,-2.2],[11.043002,42.661473,6,-0.9],[11.042865,42.66125,6,0],[11.042762,42.661101,6,0],[11.042692,42.660972,6,0],[11.042537,42.660638,6,0],[11.042496,42.660569,6,0],[11.042409,42.660462,6,-2.9],[11.042297,42.6603,5,-3.5],[11.042255,42.660229,5,0],[11.042235,42.660153,5,0],[11.042178,42.660048,5,0],[11.041992,42.659779,5,0],[11.041871,42.65957,5,0],[11.041802,42.659424,5,0],[11.041693,42.659276,5,0],[11.041533,42.659009,5,0],[11.041511,42.658958,5,0],[11.041496,42.658923,5,0],[11.041248,42.658518,5,0],[11.04123,42.658474,5,0],[11.041221,42.658421,5,0],[11.041191,42.658354,5,0],[11.041096,42.658235,5,0],[11.041029,42.658135,5,0],[11.040965,42.657992,5,0],[11.04094,42.657936,5,0],[11.040743,42.657524,5,0],[11.04066,42.657401,5,0],[11.040625,42.657327,5,0],[11.04057,42.65724,5,0],[11.040509,42.657194,5,0],[11.040444,42.657121,5,0],[11.040397,42.657031,5,0],[11.040205,42.656715,5,0],[11.040125,42.656475,5,0],[11.039967,42.656243,5,0],[11.039881,42.656075,5,0],[11.039763,42.655868,5,0],[11.039354,42.655196,5,0],[11.039292,42.65502,5,0],[11.039227,42.654954,5,0],[11.039185,42.654928,5,0],[11.039153,42.654887,5,0],[11.039022,42.654655,5,0],[11.038921,42.654413,5,0],[11.038886,42.654351,5,0],[11.038882,42.654342,5,0],[11.038753,42.654182,5,0],[11.038737,42.654133,5,8],[11.03873,42.654071,6,4.3],[11.038655,42.653931,6,0],[11.038577,42.653823,6,0],[11.038478,42.653671,6,0],[11.03846,42.653642,6,0],[11.038454,42.653633,6,0],[11.038397,42.653605,6,5.4],[11.038249,42.65356,7,4.4],[11.038153,42.653508,7,0],[11.038111,42.653475,7,0],[11.037723,42.652783,7,-0.4],[11.037024,42.651589,6,-0.7],[11.037004,42.651559,6,0],[11.037139,42.651516,6,-2.8],[11.03736,42.65138,5,-1.6],[11.03774,42.651192,5,0],[11.037902,42.651112,5,0],[11.038005,42.65133,5,0],[11.038122,42.651313,5,0],[11.038438,42.65124,5,0],[11.038954,42.651151,5,0],[11.039259,42.651125,5,0],[11.039767,42.651084,5,0],[11.040451,42.651011,5,-1.2],[11.0408,42.65095,4,-1.4],[11.04134,42.650933,4,1],[11.041955,42.650827,5,0.9],[11.042623,42.650693,5,0],[11.043186,42.650609,5,1],[11.043717,42.650296,6,1.2],[11.044112,42.650296,6,-1.6],[11.044477,42.650346,5,-1.5],[11.044918,42.650285,5,0],[11.045495,42.650101,5,0],[11.046452,42.650017,5,0],[11.046832,42.649726,5,1],[11.047417,42.649475,6,0],[11.048093,42.649346,5,-0.9],[11.048663,42.649179,5,0],[11.049248,42.649167,5,0],[11.049848,42.649084,5,0],[11.050236,42.648916,5,0],[11.051368,42.648704,5,1],[11.052424,42.648134,7,0.6],[11.052955,42.647938,6,-1.1],[11.053502,42.647882,6,-1],[11.054163,42.647865,5,0],[11.055394,42.647592,6,0.6],[11.055964,42.64743,6,0],[11.056875,42.64696,6,0],[11.057496,42.646783,6,0],[11.05803,42.646631,6,-0.5],[11.059504,42.64601,5,-0.4],[11.061023,42.645664,5,0],[11.061639,42.645602,5,0],[11.062391,42.646105,5,1.1],[11.062454,42.646118,6,2.8],[11.062809,42.646195,6,0],[11.063087,42.64616,6,0],[11.06334,42.646128,6,6.9],[11.063667,42.645859,10,2.4],[11.063903,42.645491,8,-2.2],[11.064328,42.645211,8,0],[11.064753,42.645032,8,4.2],[11.064981,42.644775,11,0],[11.06527,42.643708,8,-2.1],[11.06606,42.642814,6,-1.3],[11.066204,42.642451,6,0],[11.066189,42.642104,6,0],[11.066311,42.641819,6,0],[11.06666,42.641573,6,0],[11.06682,42.641216,6,0],[11.066829,42.640942,6,-0.8],[11.066861,42.640086,5,-0.9],[11.066865,42.639981,5,0],[11.066987,42.639534,5,5.4],[11.06722,42.639199,10,8.9],[11.067398,42.639521,8,-3.7],[11.067401,42.639672,8,0],[11.067459,42.639899,8,0],[11.067398,42.639979,8,0],[11.067401,42.640191,8,-4.1],[11.067418,42.640418,6,-3],[11.067521,42.640793,6,0],[11.067668,42.640921,6,0],[11.067713,42.64104,6,0],[11.067765,42.641024,6,0],[11.067755,42.640977,6,0],[11.067773,42.640932,6,0],[11.067815,42.640897,6,0],[11.067874,42.640879,6,0],[11.067937,42.640882,6,0],[11.067993,42.640904,6,0],[11.068031,42.640942,6,1.3],[11.068897,42.640747,7,0.7],[11.069584,42.640574,7,0],[11.070153,42.640432,7,0],[11.070344,42.640421,7,0],[11.07046,42.640444,7,0],[11.070587,42.640491,7,0],[11.070733,42.640565,7,15.7],[11.070881,42.640673,12,8.8],[11.071177,42.640956,12,0],[11.071225,42.640995,12,0],[11.071341,42.641092,12,-22],[11.071534,42.641218,4,-14.2],[11.071783,42.641473,4,5.9],[11.072419,42.642254,12,6.9],[11.072525,42.642363,12,0],[11.072634,42.642449,12,0],[11.072835,42.64259,12,12.4],[11.07378,42.643226,28,9.1],[11.074401,42.643664,28,0],[11.074404,42.643667,28,42],[11.074782,42.643987,50,37.6],[11.074832,42.644038,50,0],[11.074875,42.644144,50,0],[11.074899,42.644244,50,0],[11.074875,42.644527,50,9.6],[11.074825,42.644802,56,6.1],[11.07475,42.645409,56,0],[11.074716,42.645569,56,14],[11.074727,42.645727,61,16.4],[11.074766,42.645838,61,0],[11.074829,42.645911,61,0],[11.074889,42.645951,61,0],[11.074967,42.645989,61,0],[11.075104,42.646016,61,0],[11.075243,42.646013,61,0],[11.07539,42.645982,61,0],[11.075575,42.645929,61,16.1],[11.075884,42.645853,68,18.2],[11.076017,42.645829,68,0],[11.076166,42.645845,68,0],[11.076271,42.645882,68,0],[11.076361,42.645951,68,0],[11.07642,42.646024,68,0],[11.076452,42.646125,68,0],[11.07644,42.646206,68,0],[11.076406,42.646292,68,0],[11.076358,42.646344,68,0],[11.076291,42.646385,68,36.5],[11.075935,42.646538,84,6],[11.075777,42.646622,71,-33.3],[11.075572,42.646732,71,0],[11.075474,42.646802,71,0],[11.075416,42.646881,71,0],[11.075379,42.646978,71,0],[11.075369,42.647077,71,0],[11.075348,42.64716,71,0],[11.075306,42.647219,71,0],[11.075257,42.647269,71,0],[11.075174,42.64731,71,0],[11.075023,42.647341,71,0],[11.074897,42.647333,71,0],[11.074763,42.647337,71,-39.5],[11.074618,42.647371,61,-31.2],[11.07453,42.647422,64,15.9],[11.074451,42.647484,64,0],[11.074421,42.647534,64,0],[11.074388,42.647633,64,0],[11.074321,42.648043,64,-5.4],[11.074231,42.648456,59,-6.1],[11.074183,42.648775,59,0],[11.074189,42.649156,59,-4.5],[11.074235,42.649579,55,-5.4],[11.074294,42.649821,55,0],[11.074368,42.649999,55,-2.2],[11.074503,42.650198,54,-2.3],[11.074605,42.650346,54,24.3],[11.074763,42.650544,65,20.9],[11.074959,42.650728,65,0],[11.075092,42.650835,65,0],[11.075192,42.65091,65,6],[11.075542,42.651134,68,5.5],[11.07571,42.651219,68,0],[11.075871,42.651271,68,47],[11.07599,42.651293,81,56.4],[11.076099,42.651302,81,0],[11.076349,42.651304,81,0],[11.07672,42.65132,81,0],[11.076837,42.651356,81,0],[11.076897,42.651395,81,0],[11.076952,42.651451,81,0],[11.076989,42.651502,81,0],[11.077011,42.651589,81,0],[11.077007,42.651649,81,0],[11.076984,42.651716,81,0],[11.07691,42.651841,81,15.3],[11.076657,42.652112,89,8.8],[11.076265,42.652503,89,2],[11.07595,42.652853,91,-7.1],[11.075758,42.653159,83,-12.1],[11.075666,42.653403,83,0],[11.075627,42.653538,83,0],[11.075604,42.653707,83,-3.5],[11.075601,42.654052,81,-4.7],[11.075598,42.654089,81,-2.9],[11.075565,42.654674,79,-2.4],[11.075514,42.654846,79,0],[11.075399,42.655148,79,0],[11.075269,42.655453,79,-2.9],[11.075164,42.655743,77,-2.3],[11.074891,42.656189,77,0.8],[11.074458,42.656691,78,1.2],[11.074344,42.656812,78,0],[11.074061,42.657142,78,-6.5],[11.073351,42.657984,68,-7.5],[11.073161,42.658141,68,-24.8],[11.072988,42.65831,56,-25.4],[11.072872,42.658493,56,0],[11.072831,42.658641,56,0],[11.072797,42.658882,56,-3.8],[11.072874,42.659344,53,-2.6],[11.072949,42.659931,53,-2.4],[11.07298,42.660089,51,-3],[11.073065,42.66053,51,0],[11.07306,42.660733,51,-6.4],[11.072997,42.661092,47,-5.5],[11.072937,42.661378,47,0],[11.072888,42.66169,47,-5.4],[11.072858,42.662036,43,-3.6],[11.072933,42.662686,43,-0.9],[11.072986,42.663048,42,-1.4],[11.072995,42.663335,42,0],[11.072927,42.663918,42,0],[11.072886,42.664129,42,0],[11.072812,42.664355,42,3.4],[11.072678,42.664628,44,-8.7],[11.072289,42.665316,32,-11.6],[11.072243,42.665499,32,-8],[11.072218,42.665647,29,-9.4],[11.072217,42.665784,29,0],[11.072241,42.665925,29,0],[11.072259,42.66615,29,-6.5],[11.072305,42.666474,25,-7],[11.072314,42.666665,25,0],[11.072352,42.666964,25,1.3],[11.072377,42.667349,26,1.7],[11.072361,42.667497,26,0],[11.072318,42.667647,26,0],[11.072219,42.667871,26,0],[11.072175,42.668017,26,0],[11.072149,42.668147,26,9],[11.072172,42.668316,29,10.1],[11.072207,42.66841,29,0],[11.072218,42.66844,29,0],[11.072266,42.668517,29,0],[11.072377,42.668616,29,0],[11.072533,42.668682,29,18.8],[11.072667,42.668713,34,12.3],[11.073025,42.668698,34,0],[11.073434,42.668658,34,0],[11.073617,42.66862,34,-6.4],[11.073792,42.668563,32,-5.9],[11.073985,42.668478,32,0],[11.074356,42.66829,32,0],[11.074566,42.66824,32,0],[11.074703,42.668238,32,0],[11.074857,42.668276,32,6.2],[11.075068,42.668342,34,6.1],[11.075219,42.668398,34,0],[11.075339,42.668442,34,0],[11.076112,42.66868,34,5.6],[11.076361,42.668706,39,11.2],[11.07665,42.668716,39,4.9],[11.078103,42.668718,46,5.2],[11.078293,42.668735,46,0],[11.078463,42.668798,46,0],[11.078564,42.668873,46,23],[11.078784,42.669144,57,18.1],[11.078925,42.66934,57,0],[11.078993,42.66949,57,0],[11.079033,42.66963,57,0],[11.079064,42.669891,57,15],[11.079121,42.670099,65,20.6],[11.079173,42.670223,65,0],[11.079257,42.670357,65,0],[11.079365,42.670467,65,0],[11.079524,42.670576,65,0],[11.079677,42.670646,65,0],[11.079858,42.670703,65,-13.5],[11.08011,42.670733,60,-13.1],[11.080319,42.670728,60,0],[11.081079,42.67068,60,-3.3],[11.081415,42.670659,57,-5],[11.081809,42.670671,57,0],[11.082342,42.670699,57,0.7],[11.0836,42.670774,58,0.8],[11.083856,42.670784,58,0],[11.084095,42.670777,58,0],[11.084582,42.670717,58,6],[11.086093,42.670509,68,5.9],[11.086621,42.670442,68,0],[11.086887,42.670385,68,0],[11.087076,42.670323,68,-1.9],[11.089121,42.669497,64,-1.8],[11.089486,42.66939,64,-5],[11.089812,42.669331,61,-5.8],[11.090106,42.669299,61,0],[11.090446,42.669302,61,0],[11.090783,42.669338,61,-7.5],[11.091564,42.669461,54,-14.7],[11.09206,42.669554,45,-15.4],[11.092246,42.669588,45,-7.2],[11.094027,42.669897,33,-6.2],[11.095325,42.670102,29,-3.8],[11.095902,42.670211,27,-3.1],[11.096087,42.670261,27,0],[11.096285,42.670349,27,-3.7],[11.098119,42.671105,20,-2.6],[11.100736,42.672184,16,-1.4],[11.101198,42.672374,16,0],[11.101234,42.672335,16,0],[11.101284,42.672257,16,0],[11.101377,42.672144,16,0.6],[11.103397,42.669887,18,1.2],[11.103643,42.669595,20,4.6],[11.10369,42.669557,20,0]]}}', true);
        $path = 'testPath';
        $this->loadDem();
        $trackId = 1;
        $params = ['id' => $trackId];

        $ecTrackService = $this->partialMock(EcTrackJobsServiceProvider::class, function ($mock) use ($path) {
            $mock->shouldReceive('generateElevationChartImage')
                ->once()
                ->andReturn($path);
        });

        $this->mock(GeohubServiceProvider::class, function ($mock) use ($ecTrack, $trackId, $path) {
            $mock->shouldReceive('getEcTrack')
                ->with($trackId)
                ->once()
                ->andReturn($ecTrack);

            $mock->shouldReceive('updateEcTrack')
                ->with(
                    $trackId,
                    Mockery::on(function ($payload) use ($path) {
                        return isset($payload['elevation_chart_image'])
                            && is_string($payload['elevation_chart_image'])
                            && $payload['elevation_chart_image'] === $path;
                    })
                )
                ->once()
                ->andReturn(200);
        });

        $ecTrackService->enrichJob($params);
    }
}
