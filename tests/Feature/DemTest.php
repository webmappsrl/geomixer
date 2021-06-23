<?php

namespace Tests\Feature;

use App\Models\Dem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
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
        Artisan::call('geomixer:import_dem',
            ['name' => 'pisa_dem_100mx100m.sql']);

        // https://www.openstreetmap.org/node/613390599
        // Posizione: 43,7619795, 10,5254234
        // ele = 870

        $lon = 10.5254234;
        $lat = 43.7619795;
        // TODO check in tolerance interval
        $this->assertEquals(827, Dem::getEle($lon, $lat));

    }
}
