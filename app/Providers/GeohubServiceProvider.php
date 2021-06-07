<?php

namespace App\Providers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Intl\Exception\MissingResourceException;

define('GET_TAXONOMY_WHERE_ENDPOINT', '/api/taxonomy/where/geojson/');
define('GET_EC_MEDIA_ENDPOINT', '/api/ec/media/');
define('GET_EC_TRACK_ENDPOINT', '/api/ec/track/');
define('GET_EC_TRACK_ENRICH', '/api/ec/track/update/');
define('GET_EC_MEDIA_IMAGE_PATH_ENDPOINT', '/api/ec/media/image/');
define('GET_EC_MEDIA_ENRICH', '/api/ec/media/update/');
define('GET_ENDPOINT', '/api/');
define('CONTENT_TYPE_IMAGE_MAPPING', [
    'image/bmp' => 'bmp',
    'image/gif' => 'gif',
    'image/vnd.microsoft.icon' => 'ico',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/svg+xml' => 'svg',
    'image/tiff' => 'tif',
    'image/webp' => 'webp'
]);

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
     * Get the EcTrack from the Geohub
     *
     * @param int $id the EcTrack id to retrieve
     *
     * @return array the EcTrack selected by id
     *
     * @throws HttpException if the HTTP request fails
     */
    public function getEcTrack(int $id): array
    {
        $url = config('geohub.base_url') . GET_EC_TRACK_ENDPOINT . $id;
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
     * Get the EcMedia from the Geohub
     *
     * @param int $id the EcMedia id to retrieve
     *
     * @return array the EcMedia selected by id
     *
     * @throws HttpException if the HTTP request fails
     */
    public function getEcMedia(int $id): array
    {
        $url = config('geohub.base_url') . GET_EC_MEDIA_ENDPOINT . $id;
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
     * Get the EcMedia Image from the Geohub
     *
     * @param int $id the EcMedia id to retrieve
     *
     * @return string the EcMedia selected by id
     *
     * @throws HttpException if the HTTP request fails
     * @throws Exception
     */
    public function getEcMediaImage(int $id): string
    {
        $url = config('geohub.base_url') . GET_EC_MEDIA_IMAGE_PATH_ENDPOINT . $id;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);

        if (!isset(CONTENT_TYPE_IMAGE_MAPPING[$contentType]))
            throw new Exception('Content type not supported: ' . $contentType);

        $filename = $id . '.' . CONTENT_TYPE_IMAGE_MAPPING[$contentType];
        Storage::disk('local')->put('ec_media/' . $filename, $result);

        return Storage::disk('local')->path('ec_media/' . $filename);
    }

    /**
     * Return a geojson with the given ugc feature
     *
     * @param int $id the id of the ugc feature to retrieve
     * @param string $type the type of the ugc feature
     *
     * @return array the geojson of the Ugc in the geohub
     *
     * @throws HttpException if the HTTP request fails
     */
    public function getUgcFeature(int $id, string $type): array
    {
        return $this->getFeature($id, 'ugc/' . $type);
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
     * @param int $id the ugc feature id
     * @param string $featureType the ugc feature type
     * @param array $whereIds the where ids
     *
     * @return int the http code of the request
     *
     * @throws HttpException
     */
    public function setWheresToUgcFeature(int $id, string $featureType, array $whereIds): int
    {
        return $this->setWheresToFeature($id, 'ugc/' . $featureType, $whereIds);
    }

    /**
     * Post to Geohub the where ids that need to be associated with the specified feature
     *
     * @param int $id the feature id
     * @param string $featureType the feature type
     * @param array $whereIds the where ids
     *
     * @return int the http code of the request
     *
     * @throws HttpException
     */
    public function setWheresToFeature(int $id, string $featureType, array $whereIds): int
    {
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

    /**
     * Post to Geohub the where ids that need to be associated with the specified EcMedia
     *
     * @param int $id the EcMedia id
     * @param array $whereIds the where ids
     *
     * @return int the http code of the request
     *
     * @throws HttpException
     */
    public function setEcMediaToWhere(int $id, array $whereIds): int
    {
        $url = config('geohub.base_url') . GET_ENDPOINT . "/taxonomy_where";
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

    /**
     * Post to Geohub the parameters to update to EcMedia
     *
     * @param int $id the ecMedia id
     * @param array $exif the image exif data
     * @param array $geometry the geometry of the ecMedia
     * @param string $imageUrl
     * @param array $whereIds the ids of associated Where
     * @param array $thumbnailUrls the list of thumbnails of ecMedia
     *
     * @return int the http code of the request
     *
     */
    public function setExifAndUrlToEcMedia(int $id, array $exif, array $geometry, string $imageUrl, array $whereIds, array $thumbnailUrls): int
    {
        $url = config('geohub.base_url') . GET_EC_MEDIA_ENRICH . $id;
        $payload = [
            'exif' => $exif,
            'geometry' => $geometry,
            'url' => $imageUrl,
            'where_ids' => $whereIds,
            'thumbnail_urls' => $thumbnailUrls,
        ];
        $headers = [
            "Accept: application/json",
            "Content-Type:application/json"
        ];
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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

    /**
     * Update the ecTrack in geohub by ID
     *
     * @param int $id
     * @param float $destanceComp
     * @param array $whereIds
     * @return int
     */
    public function UpdateEcTrack(int $id, float $distanceComp, array $whereIds): int
    {
        $url = config('geohub.base_url') . GET_EC_TRACK_ENRICH . $id;
        $payload = [
            'where_ids' => $whereIds,
            'distance_comp' => $distanceComp,
        ];
        $headers = [
            "Accept: application/json",
            "Content-Type:application/json"
        ];
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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
