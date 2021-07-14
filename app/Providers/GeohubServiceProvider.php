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
define('GET_EC_POI_ENDPOINT', '/api/ec/poi/');
define('GET_EC_POI_ENRICH', '/api/ec/poi/update/');
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

        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

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

        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

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

        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

        if (!isset(CONTENT_TYPE_IMAGE_MAPPING[$contentType])) {
            throw new Exception('Content type not supported: ' . $contentType);
        }

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
        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

        if (!isset($result) || empty($result)) {
            throw new MissingResourceException('The ' . $featureType . ' with id ' . $id . ' does not exists');
        }

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

        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

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

        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

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

        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

        return $code;
    }

    /**
     * Update the ecTrack in geohub by ID.
     *
     * @param int $id
     * @param array $parameters
     *
     * @return int
     */
    public function updateEcTrack(int $id, array $parameters = []): int
    {
        $url = config('geohub.base_url') . GET_EC_TRACK_ENRICH . $id;

        $payload = [];
        $fields = [
            'geometry',
            'distance_comp',
            'distance',
            'ele_min',
            'ele_max',
            'ele_from',
            'ele_to',
            'ascent',
            'descent',
            'duration_forward',
            'duration_backward',
            'duration',
        ];

        foreach ($fields as $field) {
            if (isset($parameters[$field])) {
                $payload[$field] = $parameters[$field];
            }
        }

        if (isset($parameters['ids'])) {
            $payload['where_ids'] = $parameters['ids'];
        }

        return $this->_executePutCurl($url, $payload);
    }

    /**
     * Executes a CURL (PUT) request and returns http code
     *
     * @param string $url PUT URL
     * @param array $payload Data array
     * @return int CURL CODE
     * @throws HttpException When code is greater than 400
     */
    public function _executePutCurl(string $url, array $payload): int
    {
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

        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

        return $code;
    }

    public function getEcPoi(int $id): array
    {
        $url = config('geohub.base_url') . GET_EC_POI_ENDPOINT . $id;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

        return json_decode($result, true);
    }

    /**
     * Post to Geohub the parameters to update to EcPoi.
     *
     * @param int $id the ecPoi id
     * @param array $whereIds the ids of associated Where
     *
     * @return int the http code of the request
     *
     */
    public function setWheresToEcPoi(int $id, array $whereIds): int
    {
        $url = config('geohub.base_url') . GET_EC_POI_ENRICH . $id;
        $payload = [
            'where_ids' => $whereIds,
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
        if ($code >= 400) {
            throw new HttpException($code, 'Error ' . $code . ' calling ' . $url . ': ' . $error);
        }

        return $code;
    }
}
