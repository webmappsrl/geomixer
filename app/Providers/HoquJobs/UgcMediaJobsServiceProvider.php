<?php

namespace App\Providers\HoquJobs;

use App\Providers\GeohubServiceProvider;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Exception\ImageException;
use Intervention\Image\Facades\Image;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class UgcMediaJobsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UgcMediaJobsServiceProvider::class, function ($app) {
            return new UgcMediaJobsServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     *
     *
     * @throws Exception
     */
    public function updatePositionJob(array $params): void
    {
        if (!isset($params['id']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');

        $geohubServiceProvider = app(GeohubServiceProvider::class);

        $imagePath = $geohubServiceProvider->getUgcMediaImage($params['id']);

        $exif = $this->getImageExif($imagePath);
        if (isset($exif['coordinates'])) {
            $ugcMediaCoordinatesJson = [
                'type' => 'Point',
                'coordinates' => [$exif['coordinates'][0], $exif['coordinates'][1]]
            ];
            $taxonomyWhereJobsServiceProvider = app(TaxonomyWhereJobsServiceProvider::class);
            $ids = $taxonomyWhereJobsServiceProvider->associateWhere($ugcMediaCoordinatesJson);
        }

        // $geohubServiceProvider->updateUgcMedia($params['id'], $exif, $ugcMediaCoordinatesJson, $imageCloudUrl, $ids, $thumbnailList);
        unlink($imagePath);
    }

    /**
     * Return a mapped array with all the useful exif of the image
     *
     * @param string $imagePath the path of the image
     *
     * @return array the array with the coordinates
     *
     * @throws Exception
     */
    public function getImageExif(string $imagePath): array
    {
        if (!file_exists($imagePath))
            throw new Exception("The image $imagePath does not exists");

        $data = Image::make($imagePath)->exif();

        if (is_array($data) && in_array('GPSLatitude', $data) && in_array('GPSLongitude', $data)) {
            //Calculate Latitude with degrees, minutes and seconds
            $latDegrees = $data['GPSLatitude'][0];
            $latDegrees = explode('/', $latDegrees);
            $latDegrees = ($latDegrees[0] / $latDegrees[1]);

            $latMinutes = $data['GPSLatitude'][1];
            $latMinutes = explode('/', $latMinutes);
            $latMinutes = (($latMinutes[0] / $latMinutes[1]) / 60);

            $latSeconds = $data['GPSLatitude'][2];
            $latSeconds = explode('/', $latSeconds);
            $latSeconds = (($latSeconds[0] / $latSeconds[1]) / 3600);

            //Calculate Longitude with degrees, minutes and seconds
            $lonDegrees = $data['GPSLongitude'][0];
            $lonDegrees = explode('/', $lonDegrees);
            $lonDegrees = ($lonDegrees[0] / $lonDegrees[1]);

            $lonMinutes = $data['GPSLongitude'][1];
            $lonMinutes = explode('/', $lonMinutes);
            $lonMinutes = (($lonMinutes[0] / $lonMinutes[1]) / 60);

            $lonSeconds = $data['GPSLongitude'][2];
            $lonSeconds = explode('/', $lonSeconds);
            $lonSeconds = (($lonSeconds[0] / $lonSeconds[1]) / 3600);

            $imgLatitude = $latDegrees + $latMinutes + $latSeconds;
            $imgLongitude = $lonDegrees + $lonMinutes + $lonSeconds;

            $coordinates = [$imgLongitude, $imgLatitude];

            return array('coordinates' => $coordinates);
        } else {
            return [];
        }
    }
}
