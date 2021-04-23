<?php

namespace App\Http\Controllers;

use App\Providers\GeohubServiceProvider;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class TaxonomyWhere extends Controller {
    /**
     * Perform the update of a taxonomy where in the database
     *
     * @param array                 $params the parameters array
     * @param GeohubServiceProvider $geohubServiceProvider
     */
    public static function updateJob(array $params, GeohubServiceProvider $geohubServiceProvider): void {
        if (!isset($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');

        $id = $params['id'];

        $where = $geohubServiceProvider->getTaxonomyWhere($id);
        $currentWhere = \App\Models\TaxonomyWhere::find($id);

        if (!isset($currentWhere)) {
            $currentWhere = new \App\Models\TaxonomyWhere();
            $currentWhere->id = $id;
        }

        $currentWhere->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode($where['geometry']) . "'))");

        $currentWhere->save();
    }
}
