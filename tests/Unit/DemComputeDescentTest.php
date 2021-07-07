<?php

namespace Tests\Unit;

use App\Models\Dem;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemComputeDescentTest extends TestCase
{
    public function loadDem()
    {
        static $first = true;
        if ($first) {
            Artisan::call('geomixer:import_dem', ['name' => 'pisa_dem_100mx100m.sql']);
            $first = false;
        }
    }

    public function testDescent()
    {
        $this->loadDem();
        $geom = '{
            "type": "LineString",
            "coordinates": [
            [
                10.440509,
                43.764120,
                0
            ],
            [
                10.442032,
                43.765654,
                0
            ],
            [
                10.442891,
                43.767065,
                0
            ],
            [
                10.443770,
                43.768800,
                0
            ],
            [
                10.444521,
                43.769761,
                0
            ],
            [
                10.445637,
                43.770566,
                0
            ],
            [
                10.447204,
                43.771496,
                0
            ],
            [
                10.448534,
                43.772457,
                0
            ],
            [
                10.449156,
                43.772767,
                0
            ],
            [
                10.449907,
                43.772953,
                0
            ],
            [
                10.451173,
                43.772643,
                0
            ],
            [
                10.452096,
                43.771636,
                0
            ],
            [
                10.452804,
                43.769776,
                0
            ],
            [
                10.453169,
                43.768118,
                0
            ],
            [
                10.453362,
                43.766553,
                0
            ],
            [
                10.453062,
                43.765267,
                0
            ],
            [
                10.451045,
                43.763841,
                0
            ],
            [
                10.449178,
                43.763764,
                0
            ]
          ]
        }';

        $geom = Dem::add3D($geom);
        $info = Dem::getEleInfo($geom);
        $this->assertIsArray($info);
        $this->assertTrue(isset($info['descent']));
        $this->assertEquals(0, $info['descent']);
    }
}
