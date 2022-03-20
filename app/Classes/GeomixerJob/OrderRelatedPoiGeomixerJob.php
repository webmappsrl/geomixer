<?php

namespace App\Classes\GeomixerJob;

use App\Classes\GeomixerJob\GeomixerJob;

class OrderRelatedPoiGeomixerJob extends GeomixerJob {

    public function get() {
        $this->inputData['ecTrack'] = $this->geohub->getEcTrack($this->parameters['id']);
    }

    public function enrich() {}
    public function put() {}
}