<?php

namespace Tests\Feature;

use App\Classes\GeomixerJob\OrderRelatedPoiGeomixerJob;
use App\Providers\GeohubServiceProvider;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Tests\TestCase;

class OrderRelatedPoiGeomixerJobTest extends TestCase
{
    /**
     *
     * @return void
     * @test
     */
    public function order_related_poi_geomixer_job_has_proper_properties() {
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

        $hoqu = app(HoquServiceProvider::class);
        $geohub = app(GeohubServiceProvider::class);
        $geomixerJob = new OrderRelatedPoiGeomixerJob($job,$hoqu,$geohub);
        $this->assertEquals(100,$geomixerJob->getId());
        $this->assertEquals('myInstance',$geomixerJob->getInstance());

        $this->assertIsArray($geomixerJob->getParameters());
        $parameters = $geomixerJob->getParameters();
        $this->assertEquals($trackId,$parameters['id']);
        $this->assertEquals(TRUE,$geomixerJob->execute());

    }


    /**
     *
     * @return void
     * @test
     */
    public function order_related_poi_geomixer_with_wrong_job_parameter_throw_exception() {
        $job = [
            'id' => 100,
            'instance' => 'myInstance',
            'parameters' => json_encode(['a'=>1,'b'=>2]),
            'job' => 'wrong_parameter'
        ];
        $geohub = app(GeohubServiceProvider::class);
        $hoqu = app(HoquServiceProvider::class);

        $this->expectException(InvalidParameterException::class);

        $geomixerJob = new OrderRelatedPoiGeomixerJob($job,$hoqu,$geohub);

    }
}
