<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Intl\Exception\MissingResourceException;

define('GET_TAXONOMY_WHERE_ENDPOINT', '/api/taxonomy/where/geojson/');
define('GET_ENDPOINT', '/api/');

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
     *
     * @throws HttpException if the HTTP request fails
     */
    public function getTaxonomyWhere(int $id): array {
        $url = config('geohub.base_url') . GET_TAXONOMY_WHERE_ENDPOINT . $id;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);

        return json_decode($result, true);
    }

    /**
     * Return a geojson with the given ugc feature
     *
     * @param int    $id   the id of the ugc feature to retrieve
     * @param string $type the type of the ugc feature
     *
     * @return array the geojson of the Ugc in the geohub
     *
     * @throws HttpException if the HTTP request fails
     */
    public function getUgcFeature(int $id, string $type): array {
        return $this->getFeature($id, 'ugc/' . $type);
    }

    /**
     * Return a geojson with the given feature
     *
     * @param int    $id
     * @param string $featureType
     *
     * @return array the feature geojson
     *
     * @throws HttpException
     * @throws MissingResourceException
     */
    public function getFeature(int $id, string $featureType): array {
        $url = config('geohub.base_url') . GET_ENDPOINT . $featureType . "/geojson/" . $id;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);

        if (!isset($result) || empty($result))
            throw new MissingResourceException('The ' . $featureType . ' with id ' . $id . ' does not exists');

        return json_decode($result, true);
    }

    /**
     * Post to Geohub the where ids that need to be associated with the specified ugc feature
     *
     * @param int    $id          the ugc feature id
     * @param string $featureType the ugc feature type
     * @param array  $whereIds    the where ids
     *
     * @return int the http code of the request
     *
     * @throws HttpException
     */
    public function setWheresToUgcFeature(int $id, string $featureType, array $whereIds): int {
        return $this->setWheresToFeature($id, 'ugc/' . $featureType, $whereIds);
    }

    /**
     * Post to Geohub the where ids that need to be associated with the specified feature
     *
     * @param int    $id          the feature id
     * @param string $featureType the feature type
     * @param array  $whereIds    the where ids
     *
     * @return int the http code of the request
     *
     * @throws HttpException
     */
    public function setWheresToFeature(int $id, string $featureType, array $whereIds): int {
        $url = config('geohub.base_url') . GET_ENDPOINT . $featureType . "/taxonomy_where";
        $payload = [
            'id' => $id,
            'where_ids' => $whereIds
        ];
        $headers = [
            "Accept: application/json",
            "Content-Type:application/json"
        ];
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);

        return $code;
    }
}
