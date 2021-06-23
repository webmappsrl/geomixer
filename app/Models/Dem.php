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
}
