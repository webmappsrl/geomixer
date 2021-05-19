<?php

namespace Tests\Unit\HoquServer\Jobs;

use App\Providers\GeohubServiceProvider;
use App\Providers\HoquJobs\EcMediaJobsServiceProvider;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Tests\TestCase;


class UpdateEcMediaToWhereJobTest extends TestCase
{
    public function testWheresIdsIsMissing()
    {
        $this->mock(GeohubServiceProvider::class);
        $service = $this->partialMock(EcMediaJobsServiceProvider::class);

        try {
            $service->updateEcMediaToWhereJobs([]);
        } catch (MissingMandatoryParametersException $e) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('The function should fail but does not');
    }

    public function testPointNotContained()
    {
        $id = 1;
        $where = json_decode(File::get("tests/Fixtures/TaxonomyWhere/geohubPoint1.geojson"), true);
        $this->mock(GeohubServiceProvider::class, function ($mock) use ($id, $where) {
            $mock->shouldReceive('getEcMediaImage')
                ->once()
                ->with($id)
                ->andReturn($where);

            $mock->shouldReceive('setWheresToUgcFeature')
                ->once()
                ->with($id, [])
                ->andReturn(200);
        });
        $service = $this->partialMock(EcMediaJobsServiceProvider::class);

        \App\Models\TaxonomyWhere::factory([
            'id' => 1,
            'geometry' => DB::raw("(ST_GeomFromText('POLYGON((0 0, 0 1, 1 1, 1 0, 0 0))'))")
        ])->create();

        $service->updateEcMediaToWhereJob([
            'id' => 1,
        ]);
    }
}
