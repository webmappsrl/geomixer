<?php

namespace App\Providers\HoquJobs;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

define('TILES_TYPES', [
    'raster'
]);

class MBTilesJobsServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(MBTilesJobsServiceProvider::class, function ($app) {
            return new MBTilesJobsServiceProvider($app);
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
     * Check if the tl command exists
     *
     * @return bool
     */
    public function tlExists(): bool {
        $return = shell_exec("which tl");

        return !empty($return);
    }

    /**
     * Generate a square of MBTiles and upload it to the s3 bucket
     *
     * @param array $params must contain zoom, x, y in the XYZ format. Accepted zooms: 0, 7, 14
     *
     * @throws Exception
     */
    public function generateMBTilesSquareJob(array $params) {
        if (!isset($params['zoom']) || empty($params['zoom'])) {
            throw new MissingMandatoryParametersException('The parameter "zoom" is missing but required. The operation can not be completed');
        }
        if (!isset($params['x']) || empty($params['x'])) {
            throw new MissingMandatoryParametersException('The parameter "x" is missing but required. The operation can not be completed');
        }
        if (!isset($params['y']) || empty($params['y'])) {
            throw new MissingMandatoryParametersException('The parameter "y" is missing but required. The operation can not be completed');
        }

        if (!$this->tlExists())
            throw new Exception('The "tl" command seems to be not installed in the system. The MBTiles generation cannot continue');

        $zoom = intval(strval($params['zoom']));
        $x = intval(strval($params['x']));
        $y = intval(strval($params['y']));

        $zoomLevels = config('geomixer.hoqu.mbtiles.zoom_levels');
        if (!in_array($zoom, array_keys($zoomLevels)))
            throw new Exception("The zoom level $zoom is not supported. Supported zoom levels are: " . implode(', ', array_keys($zoomLevels)));

        $zoomLevel = $zoomLevels[$zoom];

        if ($x < $zoomLevel['x'][0] || $x > $zoomLevel['x'][1])
            throw new Exception("The x coordinate is out of the supported bounds");
        if ($y < $zoomLevel['y'][0] || $y > $zoomLevel['y'][1])
            throw new Exception("The y coordinate is out of the supported bounds");

        $type = 'raster';

        if (!in_array($type, TILES_TYPES))
            throw new Exception("Unknown tiles type $type");

        $tilesPath = config("geomixer.{$type}_tiles_path");

        if (!$tilesPath)
            throw new Exception("The tiles path must be defined to generate the mbtiles");

        switch ($type) {
            case 'raster':
                $mbtilesPath = $this->generateRasterMBTiles($zoom, $zoom + $zoomLevel['zooms'] - 1, $x, $y);
                break;
            default:
                throw new Exception("Unknown tiles type $type");
        }

        $uploadPath = "$type/$zoom/$x/$y.mbtiles";

        Storage::disk('mbtiles')->put($uploadPath, $mbtilesPath);

        Storage::disk('local')->delete($mbtilesPath);
    }

    /**
     * Generate the raster mbtiles from the configuration provided
     *
     * @param int $minZoom mbtiles min zoom
     * @param int $maxZoom mbtiles max zoom
     * @param int $x       x coordinate at the min zoom in the XYZ format
     * @param int $y       y coordinate at the min zoom in the XYZ format
     *
     * @return string the path of the generated mbtiles
     * @throws Exception
     */
    public function generateRasterMBTiles(int $minZoom, int $maxZoom, int $x, int $y): string {
        $localPath = "mbtiles/{$minZoom}_{$x}_{$y}.mbtiles";
        if (!Storage::disk('local')->exists("mbtiles"))
            Storage::disk('local')->makeDirectory("mbtiles");
        $path = Storage::disk('local')->path($localPath);
        $tilesPath = config('geomixer.raster_tiles_path');

        $bbox = $this->_getBBoxFromTileCoordinates($minZoom, $x, $y);
        $command = "tl copy -z $minZoom -Z $maxZoom -b \"$bbox[0] $bbox[1] $bbox[2] $bbox[3]\" file://$tilesPath mbtiles://$path";
        $result = exec($command, $output, $resultCode);

        if (!$result)
            throw new Exception("An error occurred trying to generate the mbtiles");

        return $localPath;
    }

    /**
     * Calculate the bbox of a tile given the tiles coordinates in the XYZ system
     *
     * @param int $zoom
     * @param int $x
     * @param int $y
     *
     * @return array
     */
    private function _getBBoxFromTileCoordinates(int $zoom, int $x, int $y): array {
        $n = pow(2, $zoom);

        $bbox = [];
        $bbox[0] = $x / $n * 360 - 180;
        $bbox[1] = atan(sinh(pi() * (1 - 2 * ($y + 1) / $n))) * 180 / pi();
        $bbox[2] = ($x + 1) / $n * 360 - 180;
        $bbox[3] = atan(sinh(pi() * (1 - 2 * $y / $n))) * 180 / pi();

        return $bbox;
    }
}
