<?php

namespace App\Providers\HoquJobs;

use App\Models\Dem;
use App\Providers\GeohubServiceProvider;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\MountManager;
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
        $slopeValues = $this->calculateSlopeValues($payload['geometry']);
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

        $payload['mbtiles'] = $this->getMbtilesArray($payload['geometry']);
        $newGeojson = [
            'type' => 'Feature',
            'properties' => $ecTrack['geometry'],
            'geometry' => $payload['geometry']
        ];
        $this->generateElevationChartImages($newGeojson);

        $geohubServiceProvider->updateEcTrack($params['id'], $payload);
    }

    /**
     *
     * @param array $geometry
     *
     * @return array|null
     */
    public function calculateSlopeValues(array $geometry): ?array {
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
     * Calculate the mbtiles that the track needs to use and return them as an array of strings
     *
     * @param array $geometry
     *
     * @return array
     */
    public function getMbtilesArray(array $geometry): array {
        $dbResults = DB::select("SELECT
	tiles.z AS z, tiles.x AS x, tiles.y AS y
	FROM tiles,
	(SELECT 
		ST_Buffer(
			ST_Transform(
				ST_SetSRID(ST_GeomFromGeojson('" . json_encode($geometry) . "'), 4326),
				3857
			),
			1000,
			'endcap=round join=round'
		) AS buffer) as track
	-- WHERE ST_Intersects(ST_TileEnvelope(tiles.z, tiles.x, tiles.y), track.buffer);
	WHERE ST_Intersects(ST_Transform(ST_MakeEnvelope(
tiles.x / POWER(2, tiles.z) * 360 - 180,
ATAN(SINH(PI() * (1 - 2 * (tiles.y + 1) / POWER(2, tiles.z)))) * 180 / PI(),
(tiles.x + 1) / POWER(2, tiles.z) * 360 - 180,
ATAN(SINH(PI() * (1 - 2 * tiles.y / POWER(2, tiles.z)))) * 180 / PI(),
4326),
3857), track.buffer)");

        foreach ($dbResults as $row) {
            $result[] = $row->z . '/' . $row->x . '/' . $row->y;
        }

        return $result ?? [];
    }

    /**
     * Generate all the elevation chart images for the ec track
     *
     * @param array $geojson
     *
     * @throws Exception when the generation fail
     */
    public function generateElevationChartImages(array $geojson): void {
        $localDisk = Storage::disk('local');
        $ecMediaDisk = Storage::disk('s3');

        if (!$localDisk->exists('elevation_charts')) {
            $localDisk->makeDirectory('elevation_charts');
        }
        if (!$localDisk->exists('geojson')) {
            $localDisk->makeDirectory('geojson');
        }

        $id = $geojson['properties']['id'];

        $localDisk->put("geojson/$id.geojson", json_encode($geojson));

        $src = $localDisk->path("geojson/$id.geojson");
        $dest = $localDisk->path("elevation_charts/$id.svg");

        $cmd = config('geomixer.node_executable') . " node/jobs/build-elevation-chart.js --geojson=$src --dest=$dest --type=svg";

        Log::info("Running node command: {$cmd}");

        $this->runElevationChartImageGeneration($cmd);

        $localDisk->delete("geojson/$id.geojson");

        if ($ecMediaDisk->exists("ecmedia/ectrack/elevation_charts/$id.svg")) {
            if ($ecMediaDisk->exists("ecmedia/ectrack/elevation_charts/{$id}_old.svg"))
                $ecMediaDisk->delete("ecmedia/ectrack/elevation_charts/{$id}_old.svg");
            $ecMediaDisk->move("ecmedia/ectrack/elevation_charts/$id.svg", "ecmedia/ectrack/elevation_charts/{$id}_old.svg");
        }
        try {
            $ecMediaDisk->writeStream("ecmedia/ectrack/elevation_charts/$id.svg", $localDisk->readStream("elevation_charts/$id.svg"));
        } catch (Exception $e) {
            Log::warning("The elevation chart image could not be written");
            if ($ecMediaDisk->exists("ecmedia/ectrack/elevation_charts/{$id}_old.svg"))
                $ecMediaDisk->move("ecmedia/ectrack/elevation_charts/{$id}_old.svg", "ecmedia/ectrack/elevation_charts/$id.svg");
        }

        if ($ecMediaDisk->exists("ecmedia/ectrack/elevation_charts/{$id}_old.svg"))
            $ecMediaDisk->delete("ecmedia/ectrack/elevation_charts/{$id}_old.svg");
    }

    /**
     * Run the effective image generation
     *
     * @param string $cmd
     *
     * @throws Exception
     */
    public function runElevationChartImageGeneration(string $cmd) {
        $descriptorSpec = array(
            0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
            2 => array("pipe", "w")    // stderr is a pipe that the child will write to
        );
        flush();

        $process = proc_open($cmd, $descriptorSpec, $pipes, realpath('./'), array());
        if (is_resource($process)) {
            while ($s = fgets($pipes[1])) {
                Log::info($s);
                flush();
            }

            if ($s = fgets($pipes[2]))
                throw new Exception($s);
        }
    }
}
