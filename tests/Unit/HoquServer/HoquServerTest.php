<?php

namespace Tests\Unit\HoquServer;

use App\Console\Commands\HoquServer;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Tests\TestCase;

class HoquServerTest extends TestCase {
    use RefreshDatabase;

    public function testNoJobsAvailable() {
        $hoquServiceMock = $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('pull')
                ->once()
                ->andReturn([]);
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertFalse($result);
    }

    public function testError() {
        $hoquServiceMock = $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('pull')
                ->once()
                ->andThrow(new MissingMandatoryParametersException('Test message'));
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertFalse($result);
    }

    public function testJobExecuted() {
        $jobParameters = [
            'id' => 2,
            'type' => 'poi'
        ];
        $job = [
            'id' => 1,
            'job' => UPDATE_UGC_TAXONOMY_WHERES,
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

        $this->mock(TaxonomyWhereJobsServiceProvider::class, function ($mock) use ($job) {
            $mock->shouldReceive('updateWheresToFeatureJob')
                ->once()
                ->andReturn();
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertTrue($result);
    }

    public function testJobTriggerError() {
        $jobParameters = [
            'id' => 2
        ];
        $job = [
            'id' => 1,
            'job' => 'update_ugc_taxonomy_wheres',
            'parameters' => json_encode($jobParameters)
        ];
        $hoquServiceMock = $this->mock(HoquServiceProvider::class, function ($mock) use ($job) {
            $mock->shouldReceive('pull')
                ->once()
                ->andReturn($job);

            $mock->shouldReceive('updateError')
                ->once()
                ->andReturn(200);
        });

        $this->mock(TaxonomyWhereJobsServiceProvider::class, function ($mock) use ($job) {
            $mock->shouldReceive('updateWheresToFeatureJob')
                ->once()
                ->andThrows(new \Exception('Test message'));
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertTrue($result);
    }

    public function testJobUnknown() {
        $jobParameters = [
            'id' => 2
        ];
        $job = [
            'id' => 1,
            'job' => 'job_completely_unknown_to_anyone',
            'parameters' => json_encode($jobParameters)
        ];
        $hoquServiceMock = $this->mock(HoquServiceProvider::class, function ($mock) use ($job) {
            $mock->shouldReceive('pull')
                ->once()
                ->andReturn($job);

            $mock->shouldReceive('updateError')
                ->once()
                ->andReturn(200);
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertTrue($result);
    }

    // TODO: implement
    public function xtestJobUpdateTaxonomyWhereSupported() {
    }

    // TODO: implement
    public function xtestJobUpdateWheresToFeatureSupported() {
    }
}
