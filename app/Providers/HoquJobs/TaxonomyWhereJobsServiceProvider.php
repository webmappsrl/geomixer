<?php

namespace App\Providers\HoquJobs;

use App\Models\TaxonomyWhere;
use App\Providers\GeohubServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class TaxonomyWhereJobsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(TaxonomyWhereJobsServiceProvider::class, function ($app) {
            return new TaxonomyWhereJobsServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Perform the update of a taxonomy where in the database
     *
     * @param array $params the parameters array
     *
     * @throws MissingMandatoryParametersException
     */
    public function updateJob(array $params): void
    {
        if (!isset($params['id']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');

        $id = $params['id'];

        $geohubServiceProvider = app(GeohubServiceProvider::class);
        $where = $geohubServiceProvider->getTaxonomyWhere($id);
        $currentWhere = TaxonomyWhere::find($id);

        if (!isset($currentWhere)) {
            $currentWhere = new TaxonomyWhere();
            $currentWhere->id = $id;
        }

        $currentWhere->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode($where['geometry']) . "'))");

        $currentWhere->save();
    }

    /**
     * Calculate the wheres to associate with the given feature
     *
     * @param array $params the HOQU job parameters
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */
    public function updateWheresToFeatureJob(array $params): void
    {
        if (!isset($params['id']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');
        if (!isset($params['type']) || empty($params['type']))
            throw new MissingMandatoryParametersException('The parameter "type" is missing but required. The operation can not be completed');

        $id = $params['id'];
        $featureType = $params['type'];

        $geohubServiceProvider = app(GeohubServiceProvider::class);
        $feature = $geohubServiceProvider->getUgcFeature($id, $featureType);

        $ids = $this->associateWhere($feature['geometry']);

        $geohubServiceProvider->setWheresToUgcFeature($id, $featureType, $ids);
    }

    /**
     * Calculate the wheres to associate with the given EcMedia
     *
     * @param array $params the HOQU job parameters
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */
    public function updateWheresToEcMediaJob(array $params): void
    {
        if (!isset($params['id']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');

        $id = $params['id'];
        $featureType = $params['type'];

        $geohubServiceProvider = app(GeohubServiceProvider::class);
        $ecMediaJobsServiceProvider = app(EcMediaJobsServiceProvider::class);

        $coordinates = $ecMediaJobsServiceProvider->enrichJob();

        $ids = $this->associateWhere($coordinates);

    }

    /**
     * @param array $geometry
     *
     * @return array the ids of associate Wheres
     */
    public function associateWhere(array $geometry)
    {
        return TaxonomyWhere::whereRaw(
            'public.ST_Intersects('
            . 'public.ST_Force2D('
            . "public.ST_GeomFromGeojson('"
            . json_encode($geometry)
            . "')"
            . ")"
            . ', geometry)'
        )->get()->pluck('id')->toArray();
    }
}
