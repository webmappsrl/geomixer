<?php

namespace Tests\Unit;

use App\Models\Dem;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 *               - ele_from
 *               - ele_to
 *               - ele_max
 *               - ele_min
 *               - ascent
 *               - descent
 *               - duration_forward
 *               - time_backward
 */
class DemGetEleInfoTest extends TestCase
{

    private $expected = [
        'distance' => 51.4,
        'ele_from' => 5,
        'ele_to' => 5,
        'ele_min' => 0,
        'ele_max' => 444,
        'ascent' => 2237,
        'descent' => 2237,
        'duration_forward' => 1265,
        'duration_backward' => 1265,
    ];

    private function _getInfo()
    {
        static $first = true;
        static $info;
        if ($first) {
            Artisan::call('geomixer:import_dem', ['name' => 'pisa_dem_100mx100m.sql']);
            $geojson = json_decode(file_get_contents(base_path('/tests/Fixtures/EcTracks/MontePisano_LineString_with3d.geojson')), true);
            $info = Dem::getEleInfo(json_encode($geojson['geometry']));
            $this->assertIsArray($info);
            $first = false;
        }
        return $info;
    }

    public function testDistance()
    {
        $this->_testField('distance');
    }

    public function testEleMax()
    {
        $this->_testField('ele_max');
    }

    public function testEleMin()
    {
        $this->_testField('ele_min');
    }

    public function testEleFrom()
    {
        $this->_testField('ele_from');
    }

    public function testEleTo()
    {
        $this->_testField('ele_to');
    }

    public function testAscent()
    {
        $this->_testField('ascent');
    }

    public function testDescent()
    {
        $this->_testField('descent');
    }

    public function testDurationForward()
    {
        $this->_testField('duration_forward');
    }

    public function testDurationBackward()
    {
        $this->_testField('duration_backward');
    }

    private function _testField($field)
    {
        $info = $this->_getInfo();
        $this->assertTrue(isset($info[$field]));
        $this->assertEquals($this->expected[$field], $info[$field]);
    }
}
