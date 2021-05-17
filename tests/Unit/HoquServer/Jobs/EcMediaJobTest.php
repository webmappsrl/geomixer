<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Console\Commands\HoquServer;
use App\Providers\HoquJobs\EcMediaJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
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
}
