<?php

namespace App\Classes\GeomixerJob;

use App\Classes\GeomixerJob\GeomixerJob;
use Illuminate\Support\Facades\DB;

class OrderRelatedPoiGeomixerJob extends GeomixerJob {

    public function get() {
        $this->inputData['ecTrack'] = $this->geohub->getEcTrack($this->parameters['id']);
    }

    // IT GETS data from ec TRACK and compute the proper order filling outputData['related_pois_order'] array
    public function enrich() {
        // CHeck if TRACK has related POIS
        if(!isset($this->inputData['ecTrack']['properties']['related_pois'])) {
            // SKIP;
            return ;
        }
        $related_pois = $this->inputData['ecTrack']['properties']['related_pois'];
        $track_geometry = $this->inputData['ecTrack']['geometry'];

        $oredered_pois = [];
        foreach($related_pois as $poi) {
            $poi_geometry = $poi['geometry'];
            // POI VAL along track https://postgis.net/docs/ST_LineLocatePoint.html
            $line = "ST_GeomFromGeoJSON('".json_encode($track_geometry)."')";
            $point = "ST_GeomFromGeoJSON('".json_encode($poi_geometry)."')";
            $sql = DB::raw("SELECT ST_LineLocatePoint($line,$point) as val;");
            $result = DB::select($sql);
            $oredered_pois[$poi['properties']['id']]=$result[0]->val;
        }
        asort($oredered_pois);
        $this->outputData['related_pois_order']=array_keys($oredered_pois);
        
    }

    public function put() {
        // Check if outputData['related_pois_order'] is set
    }
}