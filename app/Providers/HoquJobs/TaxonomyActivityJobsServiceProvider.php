<?php

namespace App\Providers\HoquJobs;

use App\Models\TaxonomyWhere;
use App\Providers\GeohubServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class TaxonomyActivityJobsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(TaxonomyActivityJobsServiceProvider::class, function ($app) {
            return new TaxonomyActivityJobsServiceProvider($app);
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
     * @param array $geometry
     *
     * @return array the ids of associate Wheres
     */
    public function calculateDuration(array $duration)
    {
        $computed = [];
        foreach ($duration as $identifier => $values) {
            $computed[$identifier] = $this->calculateDurationByIdentifier($identifier);
        }

        return $computed;
    }

    /**
     * Calculate backward and forward duration by identifier.
     * 
     * @param string TaxonomyActivity identifier
     * 
     * @return array the forward and backward calculated array
     */
    protected function calculateDurationByIdentifier(string $identifier): array
    {
        $duration = [
            'forward' => 0,
            'backward' => 0,
        ];
        switch ($identifier) {
            case 'hiking':
                $duration['forward'] = 300;
                $duration['backward'] = 250;
                break;
            case 'cycling':
                $duration['forward'] = 100;
                $duration['backward'] = 50;
                break;
        }

        return $duration;
    }
}
