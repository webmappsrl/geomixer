<?php

namespace App\Providers\HoquJobs;

use App\Models\Dem;
use App\Providers\GeohubServiceProvider;
use Exception;
use Illuminate\Support\Facades\Log;
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

        // Manage null TODO: warning / error to hoqu (?)
        if(count($ecPoi)==0) {
            Log::warning("PROBLEM WITH POI geohubServiceProvider->getEcPoi() ID=".$params['id']);
            return;
        }
        
        $ele = 0;
        if (isset($ecPoi['geometry'])) {
            $payload['ids'] = $taxonomyWhereJobServiceProvider->associateWhere($ecPoi['geometry']);
            $coordinates = $ecPoi["geometry"]["coordinates"];
            $ele = Dem::getEle($coordinates[0], $coordinates[1]);
        }
        
        $payload['ele'] = $ele;
        
        $geohubServiceProvider->updateEcPoi($params['id'], $payload);
    }
}
