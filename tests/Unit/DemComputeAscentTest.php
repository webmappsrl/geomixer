<?php

namespace Tests\Unit;

use App\Models\Dem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemComputeAscentTest extends TestCase
{
    public function loadDem()
    {
        static $first = true;
        if ($first) {
            Artisan::call('geomixer:import_dem', ['name' => 'pisa_dem_100mx100m.sql']);
            $first = false;
        }
    }

    public function testAscent()
    {
        $this->loadDem();
        $geom = '{
            "type": "LineString",
            "coordinates": [
            [
                10.495,
                43.758,
                0
            ],
            [
                10.447,
                43.740,
                0
            ],
            [
                10.445,
                43.700,
                0
            ]
        ]
      }';

        $info = Dem::getEleInfo($geom);
        $this->assertIsArray($info);
        $this->assertTrue(isset($info['ascent']));
        $this->assertEquals(0, $info['ascent']);

    }


}
