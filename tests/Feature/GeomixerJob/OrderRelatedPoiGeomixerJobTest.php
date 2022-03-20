<?php

namespace Tests\Feature;

use App\Classes\GeomixerJob\OrderRelatedPoiGeomixerJob;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
        $job = [
            'id' => 100,
            'instance' => 'myInstance',
            'parameters' => json_encode(['a'=>1,'b'=>2]),
            'job' => 'order_related_poi'
        ];
        $hoqu = app(HoquServiceProvider::class);
        $geomixerJob = new OrderRelatedPoiGeomixerJob($job,$hoqu);

        $this->assertEquals(100,$geomixerJob->getId());
        $this->assertEquals('myInstance',$geomixerJob->getInstance());

        $this->assertIsArray($geomixerJob->getParameters());
        $parameters = $geomixerJob->getParameters();
        $this->assertEquals(1,$parameters['a']);
        $this->assertEquals(2,$parameters['b']);

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
        $hoqu = app(HoquServiceProvider::class);

        $this->expectException(InvalidParameterException::class);

        $geomixerJob = new OrderRelatedPoiGeomixerJob($job,$hoqu);

    }
}
