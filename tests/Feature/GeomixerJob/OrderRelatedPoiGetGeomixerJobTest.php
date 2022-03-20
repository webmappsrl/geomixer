<?php

namespace Tests\Feature;

use App\Classes\GeomixerJob\OrderRelatedPoiGeomixerJob;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderRelatedPoiGetGeomixerJobTest extends TestCase
{

    /**
     *
     * @return void
     * @test
     */
    public function method_get_set_property_data_input_properly() {

        // PREPARE
        $trackId = 53;
        $job = [
            'id' => 100,
            'instance' => 'myInstance',
            'parameters' => json_encode(['id'=>$trackId]),
            'job' => 'order_related_poi'
        ];


        // Build MOCK geohub getTrack
        $ecTrack = json_decode(file_get_contents(base_path('tests/Fixtures/EcTracks/ec_track_53.geojson')),TRUE);

        $this->mock(GeohubServiceProvider::class, function (MockInterface $mock) use ($ecTrack,$trackId){
            $mock->shouldReceive('getEcTrack')
                ->once()
                ->with($trackId)
                ->andReturn($ecTrack);

            $mock->shouldReceive('updateEcTrack')->once();
        });

        // Creates GeomixerJobInstances (with some security assertions)
        $hoqu = app(HoquServiceProvider::class);
        $geohub = app(GeohubServiceProvider::class);
        $geomixerJob = new OrderRelatedPoiGeomixerJob($job,$hoqu,$geohub);
        $this->assertEquals(100,$geomixerJob->getId());
        $this->assertEquals('myInstance',$geomixerJob->getInstance());
        $this->assertIsArray($geomixerJob->getParameters());
        $parameters = $geomixerJob->getParameters();
        $this->assertEquals(53,$parameters['id']);

        // Execute
        $res = $geomixerJob->execute();
        $this->assertEquals(TRUE,$res);

        // Check input data
        $inputData = $geomixerJob->getInputData();
        $this->assertIsArray($inputData);
        $this->assertArrayHasKey('ecTrack',$inputData);
        $this->assertIsArray($inputData['ecTrack']);
        $ecTrack=$inputData['ecTrack'];
        $this->assertEquals(53,$ecTrack['properties']['id']);
        $this->assertIsArray($ecTrack['properties']['related_pois']);

    }


}
