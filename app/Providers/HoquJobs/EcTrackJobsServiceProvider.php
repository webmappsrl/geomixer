<?php

namespace App\Providers\HoquJobs;

use App\Models\Dem;
use App\Providers\GeohubServiceProvider;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class EcTrackJobsServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(EcTrackJobsServiceProvider::class, function ($app) {
            return new EcTrackJobsServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * Job to update the ecTrack with distance comp
     *
     * @param array $params job parameters
     *
     * @throws Exception
     */
    public function enrichJob(array $params): void {
        $taxonomyWhereJobServiceProvider = app(TaxonomyWhereJobsServiceProvider::class);
        $geohubServiceProvider = app(GeohubServiceProvider::class);
        if (!isset($params['id']) || empty($params['id'])) {
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');
        }

        $ecTrack = $geohubServiceProvider->getEcTrack($params['id']);
        $payload = [];

        /**
         * Retrieve geometry from OSM by osmid.
         *
         * @TODO: implement
         */
        //        $importMethod = $ecTrack['import_method'];
        //        if ('osm' === $importMethod && !is_null($ecTrack['source_id'])) {
        //            $ecTrack['geometry'] = $this->retrieveOsmGeometry($ecTrack['source_id']);
        //            $payload['geometry'] = $ecTrack['geometry'];
        //        }

        /**
         * Retrieve 3D profile by geometry e DEM file.
         */
        $geom3D_string = Dem::add3D(json_encode($ecTrack['geometry']));
        $payload['geometry'] = json_decode($geom3D_string, true);

        /**
         * Compute slope values
         */
        $slopeValues = $this->_calculateSlopeValues($payload['geometry']);
        if (isset($slopeValues))
            $payload['slope'] = $slopeValues;

        /**
         * Compute EleMAX
         */
        $info_ele = Dem::getEleInfo($geom3D_string);
        $payload['ele_max'] = $info_ele['ele_max'];
        $payload['ele_min'] = $info_ele['ele_min'];
        $payload['ele_from'] = $info_ele['ele_from'];
        $payload['ele_to'] = $info_ele['ele_to'];
        $payload['ascent'] = is_null($info_ele['ascent']) ? 0 : $info_ele['ascent'];
        $payload['descent'] = is_null($info_ele['descent']) ? 0 : $info_ele['descent'];
        $payload['duration_forward'] = $info_ele['duration_forward'];
        $payload['duration_backward'] = $info_ele['duration_backward'];

        /**
         * Retrieve computed distance by geometry.
         */
        $distance = round($this->getDistanceComp($ecTrack['geometry']), 1);
        $payload['distance'] = $distance;
        $payload['distance_comp'] = $distance;

        /**
         * Retrieve taxonomyWheres by geometry.
         */
        if (isset($ecTrack['geometry'])) {
            //$ids = $taxonomyWhereJobServiceProvider->associateWhere($ecTrack['geometry']);
            $payload['ids'] = $taxonomyWhereJobServiceProvider->associateWhere($ecTrack['geometry']);
        }

        /**
         * Calculate durations by activity.
         */
        if (isset($ecTrack['properties']['duration']) && isset($distance)) {
            $taxonomyActivityJobServiceProvider = app(TaxonomyActivityJobsServiceProvider::class);
            $payload['duration'] = $taxonomyActivityJobServiceProvider->calculateDuration($ecTrack['properties']['duration'], $distance, [$info_ele['ascent'], $info_ele['descent']]);
        }

        $geohubServiceProvider->updateEcTrack($params['id'], $payload);
    }

    private function _calculateSlopeValues(array $geometry): ?array {
        if (!isset($geometry['type'])
            || !isset($geometry['coordinates'])
            || $geometry['type'] !== 'LineString'
            || !is_array($geometry['coordinates'])
            || count($geometry['coordinates']) === 0)
            return null;

        $values = [];
        foreach ($geometry['coordinates'] as $key => $coordinate) {
            $firstPoint = $coordinate;
            $lastPoint = $coordinate;
            if ($key < count($geometry['coordinates']) - 1)
                $lastPoint = $geometry['coordinates'][$key + 1];

            if ($key > 0)
                $firstPoint = $geometry['coordinates'][$key - 1];

            $deltaY = $lastPoint[2] - $firstPoint[2];
            $deltaX = $this->getDistanceComp(['type' => 'LineString', 'coordinates' => [$firstPoint, $lastPoint]]) * 1000;

            $values[] = round($deltaY / $deltaX * 100, 1);
        }

        if (count($values) !== count($geometry['coordinates']))
            return null;

        return $values;
    }

    /**
     * Calculate the distance comp from geometry in KM
     *
     * @param array $geometry the ecTrack geometry
     *
     * @return float the distance comp in KMs
     */
    public function getDistanceComp(array $geometry): float {
        $distanceQuery = "SELECT ST_Length(ST_GeomFromGeoJSON('" . json_encode($geometry) . "')::geography)/1000 as length";
        $distance = DB::select(DB::raw($distanceQuery));

        return $distance[0]->length;
    }

    /**
     * Retrieve track geometry by OSM ID
     */
    public function retrieveOsmGeometry($osmid) {
        $geometry = null;

        return $geometry;
    }

    /**
     * TODO: remove me
     *
     * @param $bidimensional_geometry
     *
     * @return array
     */
    public function get3DDemProfile($bidimensional_geometry) {
        $tridimensional_geometry = $bidimensional_geometry;
        if (
            !is_null($bidimensional_geometry)
            && is_array($bidimensional_geometry)
            && isset($bidimensional_geometry['type'])
            && isset($bidimensional_geometry['coordinates'])
        ) {
            $tridimensional_geometry = [];
            $tridimensional_geometry['type'] = $bidimensional_geometry['type'];
            foreach ($bidimensional_geometry['coordinates'] as $point) {
                $tridimensional_geometry['coordinates'][] = [$point[0], $point[1], Dem::getEle($point[1], $point[0])];
            }
        }

        return $tridimensional_geometry;
    }
}
