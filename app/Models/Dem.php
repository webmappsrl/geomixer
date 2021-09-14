<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

define('MAX_ELE_MIN', 10000);
define('MIN_ELE_MAX', -10000);

class Dem extends Model {
    use HasFactory;

    /**
     * Postgis version (used to switch query string from 3.1 to 3.2)
     *
     * @return string
     */
    public static function getPostGisVersion(): string {
        $query = 'SELECT postgis_full_version() as version;';
        $res = DB::select(DB::raw($query));
        $info = explode(' ', explode('"', $res[0]->version)[1]);

        return $info[0];
    }

    public static function getEle($lon, $lat): ?int {
        switch (self::getPostGisVersion()) {
            case '3.1.2':
                $query = <<<ENDOFQUERY
SELECT
ST_Value(dem.rast,ST_FlipCoordinates(ST_Transform(ST_GeomFromText('POINT($lon $lat)', 4326),3035))) AS zeta
FROM
   dem
WHERE
ST_Intersects(dem.rast, ST_FlipCoordinates(ST_Transform(ST_GeomFromText('POINT($lon $lat)', 4326),3035)))
   ;
ENDOFQUERY;
                break;
            default :
                $query = <<<ENDOFQUERY
SELECT
ST_Value(dem.rast,ST_Transform(ST_GeomFromText('POINT($lon $lat)', 4326),3035)) AS zeta
FROM
   dem
WHERE
ST_Intersects(dem.rast, ST_Transform(ST_GeomFromText('POINT($lon $lat)', 4326),3035))
   ;
ENDOFQUERY;
                break;
        }
        $res = DB::select(DB::raw($query));
        if (is_array($res) && count($res) > 0) {
            return intval($res[0]->zeta);
        }

        return null;
    }

    /**
     * @param string $geojson_geometry
     *
     * @return string
     * @throws Exception
     */
    public static function add3D(string $geojson_geometry): string {
        $geomArray = json_decode($geojson_geometry, true);
        if (!isset($geomArray['type'])) {
            throw new Exception('No type found');
        }
        switch ($geomArray['type']) {
            case 'Point':
                $geomArray['coordinates'][2] = self::getEle($geomArray['coordinates'][0], $geomArray['coordinates'][1]);
                break;
            case 'LineString':
                $new_coord = [];
                foreach ($geomArray['coordinates'] as $point) {
                    $point[2] = self::getEle($point[0], $point[1]);
                    $new_coord[] = $point;
                }
                $geomArray['coordinates'] = $new_coord;
                break;
            default:
                throw new Exception('Type ' . $geomArray['type'] . ' not supported');
        }

        return json_encode($geomArray);
    }

    /**
     * @param string $geom geojson 3D geometry string
     *
     * @return array hash with computed ele info from 3d geometry:
     *               - distance
     *               - ele_from
     *               - ele_to
     *               - ele_max
     *               - ele_min
     *               - ascent
     *               - descent
     *               - duration_forward
     *               - time_backward
     */
    public static function getEleInfo(string $geom): array {
        $distanceQuery = "SELECT ST_Length(ST_GeomFromGeoJSON('" . $geom . "')::geography)/1000 as length";
        $res = DB::select(DB::raw($distanceQuery));
        $distance = round($res[0]->length, 1);

        $json = json_decode($geom, true);
        $ele_max = MIN_ELE_MAX;
        $ele_min = MAX_ELE_MIN;
        $ascent = 0;
        $descent = 0;
        $delta_ascents = $delta_descents = [];
        foreach ($json['coordinates'] as $j => $point) {
            if ($point[2] > $ele_max) {
                $ele_max = $point[2];
            }
            if ($point[2] < $ele_min) {
                $ele_min = $point[2];
            }
            if ($j > 0) {
                if ($point[2] > $json['coordinates'][($j - 1)][2]) {
                    $delta_ascents[] = $point[2] - $json['coordinates'][($j - 1)][2];
                } else {
                    $delta_descents[] = $json['coordinates'][($j - 1)][2] - $point[2];
                }
            }
        }
        $ele_from = $json['coordinates'][0][2];
        $ele_to = $json['coordinates'][count($json['coordinates']) - 1][2];

        foreach ($delta_ascents as $ascent_value) {
            $ascent += $ascent_value;
        }

        foreach ($delta_descents as $descent_value) {
            $descent += $descent_value;
        }

        /**
         * 3. Geomixer calculates time_forward (distance+ascent/100)/3 which results in hours, distance in Km, ascent in m
         */
        $duration_forward = ceil(($distance + $ascent / 100) / 3.5 * 60);
        $duration_backward = ceil(($distance + $descent / 100) / 3.5 * 60);

        return [
            'distance' => $distance,
            'ele_from' => $ele_from ?? null,
            'ele_to' => $ele_to ?? null,
            'ele_max' => $ele_max === MIN_ELE_MAX ? null : $ele_max,
            'ele_min' => $ele_min === MAX_ELE_MIN ? null : $ele_min,
            'ascent' => $ascent,
            'descent' => $descent,
            'duration_forward' => $duration_forward > 0 ? (int)$duration_forward : null,
            'duration_backward' => $duration_backward > 0 ? (int)$duration_backward : null
        ];
    }
}
