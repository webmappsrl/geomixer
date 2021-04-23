<?php

namespace App\Http\Controllers;

use App\Providers\GeohubServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class TaxonomyWhere extends Controller {
    /**
     * Perform the update of a taxonomy where in the database
     *
     * @param array                 $params the parameters array
     * @param GeohubServiceProvider $geohubServiceProvider
     *
     * @throws MissingMandatoryParametersException
     */
    public static function updateJob(array $params, GeohubServiceProvider $geohubServiceProvider): void {
        if (!isset($params['id']) || empty($params['id']))
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

    /**
     * Calculate the wheres to associate with the given feature
     *
     * @param array                 $params the HOQU job parameters
     * @param GeohubServiceProvider $geohubServiceProvider
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */
    public static function updateWheresToFeatureJob(array $params, GeohubServiceProvider $geohubServiceProvider): void {
        if (!isset($params['id']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');
        if (!isset($params['feature_type']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "feature_type" is missing but required. The operation can not be completed');

        $id = $params['id'];
        $featureType = $params['feature_type'];

        $feature = $geohubServiceProvider->getFeature($id, $featureType);

        $ids = \App\Models\TaxonomyWhere::whereRaw(
            'public.ST_Intersects('
            . 'public.ST_Force2D('
            . "public.ST_GeomFromGeojson('"
            . json_encode($feature['geometry'])
            . "')"
            . ")"
            . ', geometry)'
        )->get()->pluck('id')->toArray();

        $geohubServiceProvider->setWheresToFeature($id, $featureType, $ids);
    }
}
