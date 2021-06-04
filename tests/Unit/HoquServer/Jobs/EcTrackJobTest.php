<?php

namespace Tests\Unit\HoquServer\Jobs;


use App\Console\Commands\HoquServer;
use App\Providers\EcTrackJobsServiceProvider;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\EcMediaJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;


class EcTrackJobTest extends TestCase
{
    use RefreshDatabase;

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

        $this->mock(EcTrackJobsServiceProvider::class, function ($mock) use ($job) {
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
}
