<?php

namespace App\Providers\HoquJobs;

use App\Models\Dem;
use App\Providers\GeohubServiceProvider;
use Illuminate\Support\Facades\DB;
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
        if (!isset($params['id']) || empty($params['id'])) {
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');
        }

        $ecTrack = $geohubServiceProvider->getEcTrack($params['id']);
        $payload = [];

        /**
         * Retrieve geometry from OSM by osmid.
         */
        $importMethod = $ecTrack['import_method'];
        if ('osm' === $importMethod && !is_null($ecTrack['source_id'])) {
            $ecTrack['geometry'] = $this->retrieveOsmGeometry($ecTrack['source_id']);
            $payload['geometry'] = $ecTrack['geometry'];
        }

        /**
         * Retrieve 3D profile by geometry e DEM file.
         */
        $computedParameters = $this->getComputedParameters($ecTrack['geometry']);
        $payload['geometry'] = $computedParameters['geometry']);

        /**
         * Retrieve computed distance by geometry.
         */
        //$distanceComp = $this->getDistanceComp($ecTrack['geometry']);
        $payload['distance_comp'] = $this->getDistanceComp($ecTrack['geometry']);

        /**
         * Retrieve taxonomyWheres by geometry.
         */
        if (isset($ecTrack['geometry'])) {
            //$ids = $taxonomyWhereJobServiceProvider->associateWhere($ecTrack['geometry']);
            $payload['ids'] = $taxonomyWhereJobServiceProvider->associateWhere($ecTrack['geometry']);
        }

        $geohubServiceProvider->updateEcTrack($params['id'], $payload);
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

    /**
     * Retrieve track geometry by OSM ID
     */
    public function retrieveOsmGeometry($osmid)
    {
        $geometry = null;


        return $geometry;
    }

    public function getComputedParameters($bidimensional_geometry)
    {
        $computedParameters = [];
        $tridimensional_geometry = $bidimensional_geometry;
        if (
            !is_null($bidimensional_geometry)
            && is_array($bidimensional_geometry)
            && isset($bidimensional_geometry['type'])
            && isset($bidimensional_geometry['coordinates'])
        ) {
            $tridimensional_geometry = [];
            $ele_max = $ele_min = null;
            $tridimensional_geometry['type'] = $bidimensional_geometry['type'];
            foreach ($bidimensional_geometry['coordinates'] as $point) {
                $zeta = Dem::getEle($point[1], $point[0]);
                if ($zeta > $ele_max) {
                    $ele_max = $zeta;
                }
                $tridimensional_geometry['coordinates'][] = [$point[0], $point[1], $zeta];
            }

            $computedParameters['geometry'] = $tridimensional_geometry;
        }

        return $tridimensional_geometry;
    }
}
