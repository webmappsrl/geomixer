<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Console\Commands\HoquServer;
use App\Providers\HoquJobs\MBTilesJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateMBTilesSquareJobTest extends TestCase {
    public function test_job_executed() {
        $jobParameters = [
            'zoom' => 0,
            'x' => 0,
            'y' => 0
        ];
        $job = [
            'id' => 1,
            'job' => 'generate_mbtiles_square',
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

        $this->mock(MBTilesJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('generateMBTilesSquareJob')
                ->once()
                ->andReturn();
        });

        $hoquServer = new HoquServer($hoquServiceMock);
        $result = $hoquServer->executeHoquJob();
        $this->assertTrue($result);
    }

    public function test_with_no_tl_command() {
        $service = $this->partialMock(MBTilesJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('tlExists')
                ->once()
                ->andReturn(false);
        });

        try {
            $service->generateMBTilesSquareJob([
                'zoom' => 2,
                'x' => 2,
                'y' => 1
            ]);
        } catch (Exception $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail("It should have triggered an exception");
    }

    public function test_with_wrong_zoom() {
        $service = $this->partialMock(MBTilesJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('tlExists')
                ->once()
                ->andReturn(true);
        });

        try {
            $service->generateMBTilesSquareJob([
                'zoom' => 500,
                'x' => 2,
                'y' => 1
            ]);
        } catch (Exception $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail("It should have triggered an exception");
    }

    public function test_with_wrong_x() {
        $service = $this->partialMock(MBTilesJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('tlExists')
                ->once()
                ->andReturn(true);
        });

        try {
            $service->generateMBTilesSquareJob([
                'zoom' => 2,
                'x' => 1000,
                'y' => 1
            ]);
        } catch (Exception $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail("It should have triggered an exception");
    }

    public function test_with_wrong_y() {
        $service = $this->partialMock(MBTilesJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('tlExists')
                ->once()
                ->andReturn(true);
        });

        try {
            $service->generateMBTilesSquareJob([
                'zoom' => 2,
                'x' => 2,
                'y' => 100
            ]);
        } catch (Exception $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail("It should have triggered an exception");
    }

    public function test_with_no_tiles_path() {
        $service = $this->partialMock(MBTilesJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('tlExists')
                ->once()
                ->andReturn(true);
        });

        Config::set('geomixer.raster_tiles_path', null);

        try {
            $service->generateMBTilesSquareJob([
                'zoom' => 2,
                'x' => 2,
                'y' => 1
            ]);
        } catch (Exception $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail("It should have triggered an exception");
    }

    /**
     * TODO: find a way to mock the MountManager class to prevent this test from failing
     */
    public function xtest_correct_execution() {
        $service = $this->partialMock(MBTilesJobsServiceProvider::class, function ($mock) {
            $mock->shouldReceive('tlExists')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('generateRasterMBTiles')
                ->with(2, 6, 2, 1)
                ->once()
                ->andReturn('path');
        });

        Storage::extend('mock', function () {
            return \Mockery::mock(Filesystem::class);
        });

        Config::set('filesystems.disks.mbtiles', ['driver' => 'mock']);
        Config::set('filesystems.disks.local', ['driver' => 'mock']);
        $mbtilesDisk = Storage::disk('mbtiles');
        $localDisk = Storage::disk('local');

        //        Storage::shouldReceive('disk')
        //            ->with('mbtiles')
        //            ->once()
        //            ->andReturn(
        //                $mbtilesDisk
        //            );
        //        $mbtilesDisk->shouldReceive('put')->once()->andReturn();
        //        Storage::shouldReceive('disk')
        //            ->with('local')
        //            ->once()
        //            ->andReturn(
        //                $localDisk
        //            );
        //        $localDisk->shouldReceive('delete')->once()->andReturn();

        $tile_path = 'tile_path';
        Config::set('geomixer.raster_tiles_path', $tile_path);

        $service->generateMBTilesSquareJob([
            'zoom' => 2,
            'x' => 2,
            'y' => 1
        ]);
    }
}
