<?php

namespace App\Providers\HoquJobs;

use App\Providers\GeohubServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class EcTrackJobsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(EcTrackJobsServiceProvider::class, function ($app) {
            return new EcTrackJobsServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Job to update the ecTrack with distance comp
     * @param array $params job parameters
     *
     */
    public function enrichJob(array $params): void
    {
        $taxonomyWhereJobServiceProvider = app(TaxonomyWhereJobsServiceProvider::class);
        $geohubServiceProvider = app(GeohubServiceProvider::class);
        if (!isset($params['id']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');

        $ecTrack = $geohubServiceProvider->getEcTrack($params['id']);
        $distanceComp = $this->getDistanceComp($ecTrack['geometry']);
        $ids = [];
        if (isset($ecTrack['geometry'])) {
            $ids = $taxonomyWhereJobServiceProvider->associateWhere($ecTrack['geometry']);
        }
        $geohubServiceProvider->updateEcTrack($params['id'], $distanceComp, $ids);
    }

    /**
     * Calculate the distance comp from geometry in KM
     * @param array $geometry the ecTrack geometry
     * @return float the distance comp in KMs
     */
    public function getDistanceComp(array $geometry): float
    {
        $distanceQuery = "SELECT ST_Length(ST_GeomFromGeoJSON('" . json_encode($geometry) . "')::geography)/1000 as length";
        $distance = DB::select(DB::raw($distanceQuery));
        return $distance[0]->length;
    }
}
