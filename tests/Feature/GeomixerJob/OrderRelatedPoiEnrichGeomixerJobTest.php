<?php

namespace Tests\Feature;

use App\Classes\GeomixerJob\OrderRelatedPoiGeomixerJob;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderRelatedPoiEnrichGeomixerJobTest extends TestCase
{
    /**
     *
     * @return void
     * @test
     */
    public function method_put_set_property_data_output_properly() {

        // PREPARE
        $trackId = 53;
        $job = [
            'id' => 100,
            'instance' => 'myInstance',
            'parameters' => json_encode(['id'=>$trackId]),
            'job' => 'order_related_poi'
        ];


        // Build MOCK geohub getTrack
        $ecTrack = json_decode(file_get_contents(base_path('tests/Fixtures/EcTracks/ec_track_53A.geojson')),TRUE);

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

        // Check Output data
        $outputData = $geomixerJob->getOutputData();
        $this->assertIsArray($outputData);
        $this->assertArrayHasKey('related_pois_order',$outputData);
        $pois = $outputData['related_pois_order'];
        $this->assertEquals(4,count($pois));
        $this->assertEquals(3,$pois[0]);
        $this->assertEquals(2,$pois[1]);
        $this->assertEquals(1,$pois[2]);
        $this->assertEquals(0,$pois[3]);

    }

}
