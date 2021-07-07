<?php

namespace Tests\Unit;

use App\Models\Dem;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemGetEleInfoTest extends TestCase
{
    public function loadDem()
    {
        static $first = true;
        if ($first) {
            Artisan::call('geomixer:import_dem', ['name' => 'pisa_dem_100mx100m.sql']);
            $first = false;
        }
    }

    public function testEleMaxLineString()
    {
        $this->loadDem();
        $geom = '{
            "type": "LineString",
            "coordinates": [
            [
                10.495,
                43.758,
                1
            ],
            [
                10.447,
                43.740,
                2
            ]
        ]
      }';

        $info = Dem::getEleInfo($geom);
        $this->assertIsArray($info);
        $this->assertTrue(isset($info['ele_max']));
        $this->assertEquals(2, $info['ele_max']);
    }

    public function testEleMinLineString()
    {
        $this->loadDem();
        $geom = '{
            "type": "LineString",
            "coordinates": [
            [
                10.495,
                43.758,
                1
            ],
            [
                10.447,
                43.740,
                2
            ]
        ]
      }';

        $info = Dem::getEleInfo($geom);
        $this->assertIsArray($info);
        $this->assertTrue(isset($info['ele_min']));
        $this->assertEquals(1, $info['ele_min']);
    }
}
