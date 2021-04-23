<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GeohubServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(GeohubServiceProvider::class, function ($app) {
            return new GeohubServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * Get the where taxonomy from the Geohub
     *
     * @param int $id the where id to retrieve
     *
     * @return array the geojson of the where in the geohub
     */
    public function getTaxonomyWhere(int $id): array {
    }
}
