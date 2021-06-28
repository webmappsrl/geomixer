<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dem extends Model
{
    use HasFactory;

    public static function getEle($lon, $lat)
    {
        $query = <<<ENDOFQUERY
SELECT
ST_Value(dem.rast,ST_Transform(ST_GeomFromText('POINT($lon $lat)', 4326),3035)) AS zeta
FROM
   dem
WHERE
ST_Intersects(dem.rast, ST_Transform(ST_GeomFromText('POINT($lon $lat)', 4326),3035))
   ;
ENDOFQUERY;
        $res = DB::select(DB::raw($query));
        if (is_array($res) && count($res) > 0) {
            return $res[0]->zeta;
        }
    }

    /**
     * @param string $geojson_geometry
     * @return string
     */
    public static function add3D(string $geojson_geometry): string
    {
        $geomArray = json_decode($geojson_geometry, true);
        if (!isset($geomArray['type'])) {
            throw new \Exception('No type found');
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
                throw new \Exception('Type ' . $geomArray['type'] . ' not supprted');

        }
        return json_encode($geomArray);

    }
}
