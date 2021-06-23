<?php

namespace Tests\Feature;

use App\Models\Dem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testSpuntoneDiSantAllago()
    {
        // Load DEM
        Artisan::call(
            'geomixer:import_dem',
            ['name' => 'pisa_dem_100mx100m.sql']
        );

        // https://www.openstreetmap.org/node/613390599
        // Posizione: 43,7619795, 10,5254234
        // ele = 870

        $lon = 10.5254234;
        $lat = 43.7619795;
        /** @todo: check in tolerance interval */
        $this->assertEquals(827, Dem::getEle($lon, $lat));
    }

    /**
     * Test calcolo 3D da linestring.
     */
    public function testGeometry2DTo3D()
    {
        // $contentGeometry = '{"type":"FeatureCollection","features":[{"type":"Feature","properties":{},"geometry":{"type":"LineString","coordinates":[[10.257797241210938,43.73786377204876],[10.294876098632812,43.74431283565998],[10.331268310546875,43.74381677850666],[10.36285400390625,43.74431283565998],[10.396499633789062,43.752745178356285],[10.443878173828125,43.756712928570245],[10.455036163330078,43.76650718547945],[10.454521179199219,43.77394479027776],[10.456924438476562,43.7824968923812],[10.457782745361328,43.79042818348387],[10.45773983001709,43.79265866949684],[10.456624031066895,43.79364996989349],[10.453276634216309,43.792503777324505]]}}]}';
        // $geometry = DB::raw("(ST_GeomFromGeoJSON('" . json_encode($contentGeometry) . "')) as geom");
        $coordinates = [
            [10.2577972, 43.7378637],
            [10.2948760, 43.7443128],
            [10.3312683, 43.7438167],
            [10.3628540, 43.7443128],
            [10.3964996, 43.7527451],
            [10.4438781, 43.7567129],
            [10.4550361, 43.7665071],
            [10.4545211, 43.7739447],
            [10.4569244, 43.7824968],
            [10.4577827, 43.7904281],
            [10.4577398, 43.7926586],
            [10.4566240, 43.7936499],
            [10.4532766, 43.7925037],
        ];

        $this->assertIsArray($coordinates);
        $zeta = [];
        foreach ($coordinates as $point) {
            if ($value = Dem::getEle($point[1], $point[0])) {
                $zeta[] = $value;
            }
        }

        $this->assertIsArray($zeta);
        $this->assertGreaterThan(10, count($zeta));
    }
}
