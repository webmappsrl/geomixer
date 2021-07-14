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
     * @param array $activities
     * @param float $distance
     * @param array $titl array of ascent and descent values
     *
     * @return array the computed values for the duration.
     */
    public function calculateDuration(array $activities, float $distance, array $tilt): array
    {
        $computed = [];
        foreach ($activities as $identifier => $values) {
            $computed[$identifier] = $this->calculateDurationByIdentifier($identifier, $distance, $tilt);
        }

        return $computed;
    }

    /**
     * Calculate backward and forward duration by identifier.
     * 
     * @param string TaxonomyActivity identifier
     * @param float $distance
     * @param array $titl array of ascent and descent values
     * 
     * @return array the forward and backward calculated array
     */
    protected function calculateDurationByIdentifier(string $identifier, float $distance, array $tilt): array
    {
        $duration = [
            'forward' => 0,
            'backward' => 0,
        ];

        switch ($identifier) {
            case 'hiking':
                $duration['forward'] = $this->calculateBySpeed($distance, 10, $tilt[0]);
                $duration['backward'] = $this->calculateBySpeed($distance, 10, $tilt[1]);
                break;
            case 'cycling':
                $duration['forward'] = $this->calculateBySpeed($distance, 3.5, $tilt[0]);
                $duration['backward'] = $this->calculateBySpeed($distance, 3.5, $tilt[1]);
                break;
        }

        return $duration;
    }

    /**
     * duration_forward (distance+ascent/100)/3 risultato in ore, distance in Km, ascent in m.
     */
    protected function calculateBySpeed(float $distance, float $speed, float $tilt): float
    {
        return ceil(($distance + $tilt / 100) / $speed * 60);
    }
}
