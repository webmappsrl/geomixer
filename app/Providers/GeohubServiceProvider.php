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

    public function getTaxonomyWhere(int $id): array {
    }
}
