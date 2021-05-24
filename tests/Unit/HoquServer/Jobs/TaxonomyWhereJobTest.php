<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Models\TaxonomyWhere;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaxonomyWhereJobTest extends TestCase
{
    use RefreshDatabase;

    public function testIfWhereDoesNotExists()
    {
        $id = 1;
        $geohubWhere = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubWhere1.geojson"), true);
        $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $geohubWhere) {
            $mock->shouldReceive('getTaxonomyWhere')
                ->once()
                ->with($id)
                ->andReturn($geohubWhere);
        });

        $service = $this->partialMock(TaxonomyWhereJobsServiceProvider::class);
        $service->updateJob(['id' => $id]);

        $where = \App\Models\TaxonomyWhere::select([
            'id',
            DB::raw('public.ST_AsGeoJSON(geometry) as geom')
        ])->where('id', '=', $id)
            ->first();

        $this->assertNotNull($where);
        $this->assertNotNull($where->id);
        $this->assertNotNull($where->geom);
        $this->assertSame(json_encode($geohubWhere['geometry']), json_encode(json_decode($where->geom, true)));
    }

    public function testIfWhereExists()
    {
        $id = 1;
        $geohubWhere = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubWhere1.geojson"), true);
        $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $geohubWhere) {
            $mock->shouldReceive('getTaxonomyWhere')
                ->once()
                ->with($id)
                ->andReturn($geohubWhere);
        });

        $currentWhere = new \App\Models\TaxonomyWhere();
        $currentWhere->id = $id;
        $currentWhere->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode([
                'type' => 'Point',
                'coordinates' => [0, 0]
            ]) . "'))");
        $currentWhere->save();

        $service = $this->partialMock(TaxonomyWhereJobsServiceProvider::class);
        $service->updateJob(['id' => $id]);

        $where = \App\Models\TaxonomyWhere::select([
            'id',
            DB::raw('public.ST_AsGeoJSON(geometry) as geom')
        ])->where('id', '=', $id)
            ->first();

        $this->assertNotNull($where);
        $this->assertNotNull($where->id);
        $this->assertNotNull($where->geom);
        $this->assertSame(json_encode($geohubWhere['geometry']), json_encode(json_decode($where->geom, true)));
    }

    public function testAssociateWhereNoIntersect()
    {
        $service = $this->partialMock(TaxonomyWhereJobsServiceProvider::class);
        $geometry = [
            'type' => 'Point',
            'coordinates' => [100.448261111111, 434.781288888889]
        ];
        $where = TaxonomyWhere::factory(1)->create(['geometry' => DB::raw("(ST_GeomFromText('MULTIPOLYGON(((10 45, 11 45, 11 46, 11 46, 10 45)))'))")]);

        $ids = $service->associateWhere($geometry);
        $this->assertEmpty($ids);
    }

    public function testAssociateWhereIntersect()
    {
        $service = $this->partialMock(TaxonomyWhereJobsServiceProvider::class);
        $geometry = [
            'type' => 'Point',
            'coordinates' => [10, 45]
        ];
        $where = TaxonomyWhere::factory(1)->create(['geometry' => DB::raw("(ST_GeomFromText('MULTIPOLYGON(((10 45, 11 45, 11 46, 11 46, 10 45)))'))")]);

        $ids = $service->associateWhere($geometry);
        $this->assertIsArray($ids);
        $this->assertNotEmpty($ids);
    }
    
}
