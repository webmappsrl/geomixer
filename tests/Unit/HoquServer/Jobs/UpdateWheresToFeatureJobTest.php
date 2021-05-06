<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Http\Controllers\TaxonomyWhere;
use App\Providers\GeohubServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Tests\TestCase;

class UpdateWheresToFeatureJobTest extends TestCase {
    use RefreshDatabase;

    public function testFeatureIdIsMissing() {
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class);
        try {
            TaxonomyWhere::updateWheresToFeatureJob([], $geohubServiceMock);
        } catch (MissingMandatoryParametersException $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('The function should fail but does not');
    }

    public function testFeatureTypeIsMissing() {
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class);
        try {
            TaxonomyWhere::updateWheresToFeatureJob(['id' => 1], $geohubServiceMock);
        } catch (MissingMandatoryParametersException $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('The function should fail but does not');
    }

    public function testFeatureNotAvailable() {
        $id = 1;
        $featureType = 'track';
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $featureType) {
            $mock->shouldReceive('getUgcFeature')
                ->once()
                ->with($id, $featureType)
                ->andThrows(new MissingResourceException("Error - feature not found"));
        });

        try {
            TaxonomyWhere::updateWheresToFeatureJob([
                'id' => 1,
                'feature_type' => $featureType
            ], $geohubServiceMock);
        } catch (MissingResourceException $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('The function should fail but does not');
    }

    public function testGeohubNotReachableWhenRetrieving() {
        $id = 1;
        $featureType = 'track';
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $featureType) {
            $mock->shouldReceive('getUgcFeature')
                ->once()
                ->with($id, $featureType)
                ->andThrows(new HttpException(500, 'Error'));
        });

        try {
            TaxonomyWhere::updateWheresToFeatureJob([
                'id' => 1,
                'feature_type' => $featureType
            ], $geohubServiceMock);
        } catch (HttpException $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('The function should fail but does not');
    }

    public function testGeohubNotReachableWhenUpdating() {
        $id = 1;
        $featureType = 'track';
        $feature = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubTrack1.geojson"), true);
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $featureType, $feature) {
            $mock->shouldReceive('getUgcFeature')
                ->once()
                ->with($id, $featureType)
                ->andReturn($feature);

            $mock->shouldReceive('setWheresToFeature')
                ->once()
                ->with($id, $featureType, [])
                ->andThrows(new HttpException(500, 'Error'));
        });

        try {
            TaxonomyWhere::updateWheresToFeatureJob([
                'id' => 1,
                'feature_type' => $featureType
            ], $geohubServiceMock);
        } catch (HttpException $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('The function should fail but does not');
    }

    public function testPointNotContained() {
        $id = 1;
        $featureType = 'track';
        $feature = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubPoint1.geojson"), true);
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $featureType, $feature) {
            $mock->shouldReceive('getUgcFeature')
                ->once()
                ->with($id, $featureType)
                ->andReturn($feature);

            $mock->shouldReceive('setWheresToFeature')
                ->once()
                ->with($id, $featureType, [])
                ->andReturn(200);
        });

        \App\Models\TaxonomyWhere::factory([
            'id' => 1,
            'geometry' => DB::raw("(ST_GeomFromText('POLYGON((0 0, 0 1, 1 1, 1 0, 0 0))'))")
        ])->create();

        TaxonomyWhere::updateWheresToFeatureJob([
            'id' => 1,
            'feature_type' => $featureType
        ], $geohubServiceMock);
    }

    public function testPointContained() {
        $id = 1;
        $featureType = 'poi';
        $feature = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubPoint1.geojson"), true);
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $featureType, $feature) {
            $mock->shouldReceive('getUgcFeature')
                ->once()
                ->with($id, $featureType)
                ->andReturn($feature);

            $mock->shouldReceive('setWheresToFeature')
                ->once()
                ->with($id, $featureType, [1])
                ->andReturn(200);
        });

        \App\Models\TaxonomyWhere::factory([
            'id' => 1,
            'geometry' => DB::raw("(ST_GeomFromText('POLYGON((10.5 42.5, 11.5 42.5, 11.5 43.5, 10.5 43.5, 10.5 42.5))'))")
        ])->create();

        TaxonomyWhere::updateWheresToFeatureJob([
            'id' => 1,
            'feature_type' => $featureType
        ], $geohubServiceMock);
    }

    public function testLineStringNotContained() {
        $id = 1;
        $featureType = 'track';
        $feature = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubTrack1.geojson"), true);
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $featureType, $feature) {
            $mock->shouldReceive('getUgcFeature')
                ->once()
                ->with($id, $featureType)
                ->andReturn($feature);

            $mock->shouldReceive('setWheresToFeature')
                ->once()
                ->with($id, $featureType, [])
                ->andReturn(200);
        });

        \App\Models\TaxonomyWhere::factory([
            'id' => 1,
            'geometry' => DB::raw("(ST_GeomFromText('POLYGON((0 0, 0 1, 1 1, 1 0, 0 0))'))")
        ])->create();

        TaxonomyWhere::updateWheresToFeatureJob([
            'id' => 1,
            'feature_type' => $featureType
        ], $geohubServiceMock);
    }

    public function testLineStringContained() {
        $id = 1;
        $featureType = 'track';
        $feature = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubTrack1.geojson"), true);
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $featureType, $feature) {
            $mock->shouldReceive('getUgcFeature')
                ->once()
                ->with($id, $featureType)
                ->andReturn($feature);

            $mock->shouldReceive('setWheresToFeature')
                ->once()
                ->with($id, $featureType, [1])
                ->andReturn(200);
        });

        \App\Models\TaxonomyWhere::factory([
            'id' => 1,
            'geometry' => DB::raw("(ST_GeomFromText('POLYGON((10.5 42.5, 11.5 42.5, 11.5 43.5, 10.5 43.5, 10.5 42.5))'))")
        ])->create();

        TaxonomyWhere::updateWheresToFeatureJob([
            'id' => 1,
            'feature_type' => $featureType
        ], $geohubServiceMock);
    }

    public function testLineStringInMultiplePolygons() {
        $id = 1;
        $featureType = 'track';
        $feature = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubTrack1.geojson"), true);
        $geohubServiceMock = $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $featureType, $feature) {
            $mock->shouldReceive('getUgcFeature')
                ->once()
                ->with($id, $featureType)
                ->andReturn($feature);

            $mock->shouldReceive('setWheresToFeature')
                ->once()
                ->with($id, $featureType, [1, 2])
                ->andReturn(200);
        });

        \App\Models\TaxonomyWhere::factory([
            'id' => 1,
            'geometry' => DB::raw("(ST_GeomFromText('POLYGON((10.5 42.5, 11.5 42.5, 11.5 43.5, 10.5 43.5, 10.5 42.5))'))")
        ])->create();
        \App\Models\TaxonomyWhere::factory([
            'id' => 2,
            'geometry' => DB::raw("(ST_GeomFromText('POLYGON((11.5 43.5, 12.5 43.5, 12.5 44.5, 11.5 44.5, 11.5 43.5))'))")
        ])->create();
        \App\Models\TaxonomyWhere::factory([
            'id' => 3,
            'geometry' => DB::raw("(ST_GeomFromText('POLYGON((0 0, 0 1, 1 1, 1 0, 0 0))'))")
        ])->create();

        TaxonomyWhere::updateWheresToFeatureJob([
            'id' => 1,
            'feature_type' => $featureType
        ], $geohubServiceMock);
    }
}
