<?php

namespace App\Providers\HoquJobs;

use App\Providers\GeohubServiceProvider;
use Exception;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class EcPoiJobsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(EcPoiJobsServiceProvider::class, function ($app) {
            return new EcPoiJobsServiceProvider($app);
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
     *
     *
     * @throws Exception
     */
    public function enrichJob(array $params): void
    {
        $taxonomyWhereJobServiceProvider = app(TaxonomyWhereJobsServiceProvider::class);
        $geohubServiceProvider = app(GeohubServiceProvider::class);
        if (!isset($params['id']) || empty($params['id'])) {
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');
        }

        $ecPoi = $geohubServiceProvider->getEcPoi($params['id']);

        $ids = $taxonomyWhereJobServiceProvider->associateWhere($ecPoi['geometry']);

        $geohubServiceProvider->setWheresToEcPoi($params['id'], $ids);
    }
}