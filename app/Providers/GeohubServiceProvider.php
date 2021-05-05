<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Intl\Exception\MissingResourceException;

define('GET_TAXONOMY_WHERE_ENDPOINT', '/api/taxonomy/where/geojson/');

class GeohubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(GeohubServiceProvider::class, function ($app) {
            return new GeohubServiceProvider($app);
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
     * Get the where taxonomy from the Geohub
     *
     * @param int $id the where id to retrieve
     *
     * @return array the geojson of the where in the geohub
     *
     * @throws HttpException if the HTTP request fails
     */
    public function getTaxonomyWhere(int $id): array
    {
        $ch = curl_init(config('geohub.base_url') . GET_TAXONOMY_WHERE_ENDPOINT . $id);

        //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_getHeaders());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . config('geohub.base_url') . ': ' . $error);

        return json_decode($result, true);

    }

    /**
     * Return a geojson with the given feature
     *
     * @param int $id
     * @param string $featureType
     *
     * @return array the feature geojson
     *
     * @throws HttpException
     * @throws MissingResourceException
     */
    public function getFeature(int $id, string $featureType): array
    {
    }

    /**
     * Post to Geohub the where ids that need to be associated with the specified feature
     *
     * @param int $id the feature id
     * @param string $featureType the feature type
     * @param array $ids the where ids
     *
     * @return int the http code of the request
     *
     * @throws HttpException
     */
    public function setWheresToFeature(int $id, string $featureType, array $ids): int
    {
    }
}
