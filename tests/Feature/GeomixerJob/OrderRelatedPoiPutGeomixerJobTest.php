<?php

namespace Tests\Feature;

use App\Classes\GeomixerJob\OrderRelatedPoiGeomixerJob;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderRelatedPoiPutGeomixerJobTest extends TestCase
{
        /**
     *
     * @return void
     * @test
     */
    public function method_put_upload_data_properly() {

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

        $payload = [
            'related_pois_order' => [3,2,1,0],
        ];
        $this->mock(GeohubServiceProvider::class, function (MockInterface $mock) use ($ecTrack,$trackId,$payload){
            $mock->shouldReceive('getEcTrack')
                ->once()
                ->with($trackId)
                ->andReturn($ecTrack);

            $mock->shouldReceive('updateEcTrack')
                ->once()
                ->with($trackId,$payload);

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

    }
}
