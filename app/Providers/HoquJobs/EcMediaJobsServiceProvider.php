<?php

namespace App\Providers\HoquJobs;

use App\Models\EcMedia;
use App\Models\TaxonomyWhere;
use App\Providers\GeohubServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;


class EcMediaJobsServiceProvider extends ServiceProvider
{
    private $thumbnailSizes = [
        ['width' => 108, 'height' => 148],
        ['width' => 108, 'height' => 137],
        ['width' => 225, 'height' => 100],
        ['width' => 118, 'height' => 138],
        ['width' => 108, 'height' => 139],
        ['width' => 118, 'height' => 117],
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(EcMediaJobsServiceProvider::class, function ($app) {
            return new EcMediaJobsServiceProvider($app);
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
     */
    public function enrichJob(array $params): void
    {

        $thumbnailList = [];
        $taxonomyWhereJobServiceProvider = app(TaxonomyWhereJobsServiceProvider::class);
        $geohubServiceProvider = app(GeohubServiceProvider::class);
        if (!isset($params['id']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');

        $imagePath = $geohubServiceProvider->getEcMediaImage($params['id']);

        $exif = $this->getImageExif($imagePath);
        $ids = [];
        if (isset($exif['coordinates'])) {
            $ecMediaCoordinatesJson = [
                'type' => 'Point',
                'coordinates' => [$exif['coordinates'][0], $exif['coordinates'][1]]
            ];
            $ids = $taxonomyWhereJobServiceProvider->associateWhere($ecMediaCoordinatesJson);
        }
        try {
            $this->uploadEcMediaImage($imagePath);

            foreach ($this->thumbnailSizes as $size) {
                $imageResize = $this->imgResize($imagePath, $size['width'], $size['height']);
                $thumbnailUrl = $this->uploadEcMediaImageResize($imageResize, $size['width'], $size['height']);
                $key = $size['width'] . 'x' . $size['height'];
                array_push($thumbnailList, [$key => $thumbnailUrl]);
            }
            $imageCloudUrl = Storage::cloud()->url($imagePath);
            $geohubServiceProvider->setExifAndUrlToEcMedia($params['id'], $exif, $ecMediaCoordinatesJson, $imageCloudUrl, $ids, $thumbnailList);

        } catch (\Exception $e) {
            throw new \Exception('Upload Failed');
        }

        //unlink($imagePath);
    }

    /**
     * @param string $imagePath the path of the image
     *
     * @return array the array with coordinates
     * @throws HttpException if the HTTP request fails
     * @throws \Exception if image does not have GPS metadata
     *
     */
    public function getImageExif($imagePath): array
    {

        if (!file_exists($imagePath)) {
            throw new HttpException(404);
        }

        $data = Image::make($imagePath)->exif();

        if (in_array('GPSLatitude', $data) && in_array('GPSLongitude', $data)) {

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

    /**
     * @param string $imagePath the path of the image to upload
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadEcMediaImage($imagePath)
    {
        if (!file_exists($imagePath))
            return response()->json('Element does not exists', 404);

        $filename = pathinfo($imagePath)['filename'];

        Storage::disk('s3')->put('EcMedia/' . $filename, file_get_contents($imagePath));
        return response()->json('Upload Completed');
    }

    public function uploadEcMediaImageResize($imagePath, $width, $height)
    {

        if (!file_exists($imagePath))
            return response()->json('Element does not exists', 404);

        $filename = basename($imagePath);

        Storage::disk('s3')->put('EcMedia/Resize/' . $width . 'x' . $height . DIRECTORY_SEPARATOR . $filename, file_get_contents($imagePath));
        return Storage::cloud()->url($imagePath);
    }

    /**
     * @param string $imagePath the path of the image
     * @param int $width the new width
     * @param int $height the new height
     *
     * @return string the new path image
     */
    public function imgResize($imagePath, $width, $height)
    {
        $img = Image::make($imagePath);
        $pathinfo = pathinfo($imagePath);
        $newPathImage = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $this->resizedFileName($imagePath, $width, $height);
        $img->resize($width, $height, function ($const) {
            $const->aspectRatio();
        })->save($newPathImage);

        return $newPathImage;
    }

    /**
     * @param string $imagePath absolute path of file
     * @param $width
     * @param $height
     *
     * @return string
     */
    public function resizedFileName($imagePath, $width, $height)
    {
        $pathinfo = pathinfo($imagePath);
        return $pathinfo['filename'] . '_' . $width . 'x' . $height . '.' . $pathinfo['extension'];
    }


}