<?php

/**
 * The following piece is used to override php built in function by overriding
 * their behaviour inside the namespace
 */

namespace App\Providers\HoquJobs;

function unlink(string $path)
{
    return true;
}

namespace Tests\Unit\HoquServer\Jobs;

use App\Console\Commands\HoquServer;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use App\Providers\HoquJobs\UgcMediaJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateUgcMediaPositionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_executed()
    {
        $jobParameters = [
            'id' => 1,
        ];
        $job = [
            'id' => 1,
            'job' => UPDATE_UGC_MEDIA_POSITION,
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

        $this->mock(UgcMediaJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('updatePositionJob')
                ->once()
                ->andReturn();
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertTrue($result);
    }

    public function test_with_coordinates()
    {
        $id = 1;
        $fakeImgPath = 'fakeImgPath';
        $fakeCoordinates = [10, 10];
        $fakeGeometry = [
            'type' => 'Point',
            'coordinates' => $fakeCoordinates
        ];
        $fakeWhereIds = [1, 2, 3, 4];
        $fakeImageExif = [
            'coordinates' => $fakeCoordinates
        ];
        $params = [
            'id' => $id
        ];
        $geohubService = $this->mock(
            GeohubServiceProvider::class,
            function ($mock) use ($id, $fakeImgPath, $fakeGeometry, $fakeWhereIds) {
                $mock->shouldReceive('getUgcMediaImage')
                    ->once()
                    ->with($id)
                    ->andReturn($fakeImgPath);

                $mock->shouldReceive('updateUgcMedia')
                    ->once()
                    ->with(
                        $id,
                        $fakeGeometry,
                        $fakeWhereIds
                    )
                    ->andReturn(204);
            }
        );
        $taxonomyWhereService = $this->mock(TaxonomyWhereJobsServiceProvider::class, function ($mock) use ($fakeWhereIds) {
            $mock->shouldReceive('associateWhere')
                ->once()
                ->andReturn($fakeWhereIds);
        });
        $partialMock = $this->partialMock(UgcMediaJobsServiceProvider::class, function ($mock) use ($fakeImgPath, $fakeImageExif) {
            $mock->shouldReceive('getImageExif')
                ->once()
                ->with($fakeImgPath)
                ->andReturn($fakeImageExif);
        });

        $partialMock->updatePositionJob($params);
    }
}
