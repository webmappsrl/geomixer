<?php

namespace Tests\Unit;

use App\Models\Dem;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemAdd3D extends TestCase
{


    public function loadDem()
    {
        static $first = true;
        if ($first) {
            // Load DEM
            Artisan::call(
                'geomixer:import_dem',
                ['name' => 'pisa_dem_100mx100m.sql']
            );
            $first = false;
        }

    }

    public function testNoType()
    {
        $this->expectException(\Exception::class);
        Dem::add3D('x');
    }

    public function testAdd3DtoPointWithout3D()
    {
        $this->loadDem();
        $geom = '{
        "type": "Point",
        "coordinates": [
            10.495,
            43.758
        ]
      }';

        $geom3D = Dem::add3D($geom);
        $this->assertIsString($geom3D);
        $jsonGeom = json_decode($geom3D, TRUE);
        $this->assertTrue(isset($jsonGeom['type']));
        $this->assertEquals('Point', $jsonGeom['type']);
        $this->assertTrue(isset($jsonGeom['coordinates']));
        $this->assertIsArray($jsonGeom['coordinates']);
        $this->assertCount(3, $jsonGeom['coordinates']);
        $this->assertEquals(10.495, $jsonGeom['coordinates'][0]);
        $this->assertEquals(43.758, $jsonGeom['coordinates'][1]);
        $this->assertEquals(776, $jsonGeom['coordinates'][2]);

    }

    public function testAdd3DtoPointWith3D()
    {
        $this->loadDem();
        $geom = '{
        "type": "Point",
        "coordinates": [
            10.495,
            43.758,
            0
        ]
      }';

        $geom3D = Dem::add3D($geom);
        $this->assertIsString($geom3D);
        $jsonGeom = json_decode($geom3D, TRUE);
        $this->assertTrue(isset($jsonGeom['type']));
        $this->assertEquals('Point', $jsonGeom['type']);
        $this->assertTrue(isset($jsonGeom['coordinates']));
        $this->assertIsArray($jsonGeom['coordinates']);
        $this->assertCount(3, $jsonGeom['coordinates']);
        $this->assertEquals(10.495, $jsonGeom['coordinates'][0]);
        $this->assertEquals(43.758, $jsonGeom['coordinates'][1]);
        $this->assertEquals(776, $jsonGeom['coordinates'][2]);
    }

    public function testAdd3DtoLineStringWithout3D()
    {
        $geom = '{
            "type": "LineString",
            "coordinates": [
            [
                10.495,
                43.758
            ],
            [
                10.447,
                43.740
            ]
        ]
      }';

        $geom3D = Dem::add3D($geom);
        $this->assertIsString($geom3D);
        $jsonGeom = json_decode($geom3D, TRUE);
        $this->assertTrue(isset($jsonGeom['type']));
        $this->assertEquals('LineString', $jsonGeom['type']);
        $this->assertTrue(isset($jsonGeom['coordinates']));
        $this->assertIsArray($jsonGeom['coordinates']);
        $this->assertCount(2, $jsonGeom['coordinates']);
        $this->assertCount(3, $jsonGeom['coordinates'][0]);
        $this->assertCount(3, $jsonGeom['coordinates'][1]);

        $this->assertEquals(10.495, $jsonGeom['coordinates'][0][0]);
        $this->assertEquals(43.758, $jsonGeom['coordinates'][0][1]);
        $this->assertEquals(776, $jsonGeom['coordinates'][0][2]);

        $this->assertEquals(10.447, $jsonGeom['coordinates'][1][0]);
        $this->assertEquals(43.740, $jsonGeom['coordinates'][1][1]);
        $this->assertEquals(-1, $jsonGeom['coordinates'][1][2]);

    }

    public function testAdd3DtoLineStringWithout3DWithThreePoints()
    {
        $geom = '{
            "type": "LineString",
            "coordinates": [
            [
                10.495,
                43.758
            ],
            [
                10.495,
                43.758
            ],
            [
                10.447,
                43.740
            ]
        ]
      }';

        $geom3D = Dem::add3D($geom);
        $this->assertIsString($geom3D);
        $jsonGeom = json_decode($geom3D, TRUE);
        $this->assertTrue(isset($jsonGeom['type']));
        $this->assertEquals('LineString', $jsonGeom['type']);
        $this->assertTrue(isset($jsonGeom['coordinates']));
        $this->assertIsArray($jsonGeom['coordinates']);
        $this->assertCount(3, $jsonGeom['coordinates']);
        $this->assertCount(3, $jsonGeom['coordinates'][0]);
        $this->assertCount(3, $jsonGeom['coordinates'][1]);
        $this->assertCount(3, $jsonGeom['coordinates'][2]);

        $this->assertEquals(10.495, $jsonGeom['coordinates'][0][0]);
        $this->assertEquals(43.758, $jsonGeom['coordinates'][0][1]);
        $this->assertEquals(776, $jsonGeom['coordinates'][0][2]);

        $this->assertEquals(10.495, $jsonGeom['coordinates'][1][0]);
        $this->assertEquals(43.758, $jsonGeom['coordinates'][1][1]);
        $this->assertEquals(776, $jsonGeom['coordinates'][1][2]);

        $this->assertEquals(10.447, $jsonGeom['coordinates'][2][0]);
        $this->assertEquals(43.740, $jsonGeom['coordinates'][2][1]);
        $this->assertEquals(-1, $jsonGeom['coordinates'][2][2]);

    }

    public function testAdd3DtoLineStringWith3D()
    {
        $geom = '{
            "type": "LineString",
            "coordinates": [
            [
                10.495,
                43.758,
                -100
            ],
            [
                10.447,
                43.740,
                -100
            ]
        ]
      }';

        $geom3D = Dem::add3D($geom);
        $this->assertIsString($geom3D);
        $jsonGeom = json_decode($geom3D, TRUE);
        $this->assertTrue(isset($jsonGeom['type']));
        $this->assertEquals('LineString', $jsonGeom['type']);
        $this->assertTrue(isset($jsonGeom['coordinates']));
        $this->assertIsArray($jsonGeom['coordinates']);
        $this->assertCount(2, $jsonGeom['coordinates']);
        $this->assertCount(3, $jsonGeom['coordinates'][0]);
        $this->assertCount(3, $jsonGeom['coordinates'][1]);

        $this->assertEquals(10.495, $jsonGeom['coordinates'][0][0]);
        $this->assertEquals(43.758, $jsonGeom['coordinates'][0][1]);
        $this->assertEquals(776, $jsonGeom['coordinates'][0][2]);

        $this->assertEquals(10.447, $jsonGeom['coordinates'][1][0]);
        $this->assertEquals(43.740, $jsonGeom['coordinates'][1][1]);
        $this->assertEquals(-1, $jsonGeom['coordinates'][1][2]);


    }
}
