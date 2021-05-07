<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TaxonomyWhereJobTest extends TestCase {
    use RefreshDatabase;

    public function testIfWhereDoesNotExists() {
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

    public function testIfWhereExists() {
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
}
