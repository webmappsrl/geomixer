<?php

namespace Tests\Feature;

use App\Classes\GeomixerJob\TemplateGeomixerJob;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GeomixerJobTest extends TestCase
{
    /**
     *
     * @return void
     * @test
     */
    public function gemoixer_concrete_class_has_proper_properties() {
        $job = [
            'id' => 100,
            'instance' => 'myInstance',
            'parameters' => json_encode(['a'=>1,'b'=>2]),
            'job' => 'order_related_poi'
        ];
        $hoqu = app(HoquServiceProvider::class);
        $geomixerJob = new TemplateGeomixerJob($job,$hoqu);

        $this->assertEquals(100,$geomixerJob->getId());
        $this->assertEquals('myInstance',$geomixerJob->getInstance());

        $this->assertIsArray($geomixerJob->getParameters());
        $parameters = $geomixerJob->getParameters();
        $this->assertEquals(1,$parameters['a']);
        $this->assertEquals(2,$parameters['b']);

        $this->assertEquals(TRUE,$geomixerJob->execute());

    }
}
