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

define("THUMBNAIL_SIZES", [
    ['width' => 108, 'height' => 148],
    ['width' => 108, 'height' => 137],
    ['width' => 225, 'height' => 100],
    ['width' => 118, 'height' => 138],
    ['width' => 108, 'height' => 139],
    ['width' => 118, 'height' => 117],
    ['width' => 335, 'height' => 250],
    ['width' => 400, 'height' => 200],
    ['width' => 1440, 'height' => 500],
    ['width' => 1920, 'height' => 0],
]);

class EcMediaJobsServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(EcMediaJobsServiceProvider::class, function ($app) {
            return new EcMediaJobsServiceProvider($app);
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
     *
     *
     * @throws Exception
     */
    public function enrichJob(array $params): void {
        $thumbnailList = [];
        $taxonomyWhereJobServiceProvider = app(TaxonomyWhereJobsServiceProvider::class);
        $geohubServiceProvider = app(GeohubServiceProvider::class);
        if (!isset($params['id']) || empty($params['id']))
            throw new MissingMandatoryParametersException('The parameter "id" is missing but required. The operation can not be completed');

        $imagePath = $geohubServiceProvider->getEcMediaImage($params['id']);

        $exif = $this->getImageExif($imagePath);
        $ids = [];
        $ecMediaCoordinatesJson = [];
        if (isset($exif['coordinates'])) {
            $ecMediaCoordinatesJson = [
                'type' => 'Point',
                'coordinates' => [$exif['coordinates'][0], $exif['coordinates'][1]]
            ];
            $ids = $taxonomyWhereJobServiceProvider->associateWhere($ecMediaCoordinatesJson);
        }

        $imageCloudUrl = $this->uploadEcMediaImage($imagePath);

        foreach (THUMBNAIL_SIZES as $size) {
            try {
                if ($size['width'] == 0) {
                    $imageResize = $this->imgResizeSingleDimension($imagePath, $size['height'], 'height');
                } elseif ($size['height'] == 0) {
                    $imageResize = $this->imgResizeSingleDimension($imagePath, $size['width'], 'width');
                }
                $imageResize = $this->imgResize($imagePath, $size['width'], $size['height']);
                if (file_exists($imageResize)) {
                    $thumbnailUrl = $this->uploadEcMediaImageResize($imageResize, $size['width'], $size['height']);
                    $key = $size['width'] . 'x' . $size['height'];
                    $thumbnailList[$key] = $thumbnailUrl;
                }
            } catch (Exception $e) {
                Log::warning($e->getMessage());
            }
        }

        $geohubServiceProvider->setExifAndUrlToEcMedia($params['id'], $exif, $ecMediaCoordinatesJson, $imageCloudUrl, $ids, $thumbnailList);
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
    public function getImageExif(string $imagePath): array {
        if (!file_exists($imagePath))
            throw new Exception("The image $imagePath does not exists");

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
     * Upload an existing image to the s3 bucket
     *
     * @param string $imagePath the path of the image to upload
     *
     * @return string the uploaded image url
     *
     * @throws Exception
     */
    public function uploadEcMediaImage(string $imagePath): string {
        if (!file_exists($imagePath))
            throw new Exception("The image $imagePath does not exists");

        $filename = pathinfo($imagePath)['filename'] . '.' . pathinfo($imagePath)['extension'];

        $cloudPath = 'EcMedia/' . $filename;
        Storage::disk('s3')->put('EcMedia/' . $filename, file_get_contents($imagePath));

        return Storage::cloud()->url($cloudPath);
    }

    /**
     * Upload an already resized image to the s3 bucket
     *
     * @param string $imagePath the resized image
     * @param int    $width     the image width
     * @param int    $height    the image height
     *
     * @return string the uploaded image url
     *
     * @throws Exception
     */
    public function uploadEcMediaImageResize(string $imagePath, int $width, int $height): string {
        if (!file_exists($imagePath))
            throw new Exception("The image $imagePath does not exists");

        $filename = basename($imagePath);
        if ($width == 0)
            $cloudPath = 'EcMedia/Resize/x' . $height . DIRECTORY_SEPARATOR . $filename;
        elseif ($height == 0)
            $cloudPath = 'EcMedia/Resize/' . $width . 'x' . DIRECTORY_SEPARATOR . $filename;
        else
            $cloudPath = 'EcMedia/Resize/' . $width . 'x' . $height . DIRECTORY_SEPARATOR . $filename;
        Storage::disk('s3')->put($cloudPath, file_get_contents($imagePath));

        return Storage::cloud()->url($cloudPath);
    }

    /**
     * Resize the given image to the specified width and height
     *
     * @param string $imagePath the path of the image
     * @param int    $width     the new width
     * @param int    $height    the new height
     *
     * @return string the new path image
     *
     * @throws ImageException
     */
    public function imgResize(string $imagePath, int $width, int $height): string {
        list($imgWidth, $imgHeight) = getimagesize($imagePath);
        if ($imgWidth < $width || $imgHeight < $height)
            throw new ImageException("The image is too small to resize - required size: $width, $height - actual size: $imgWidth, $imgHeight");

        $img = Image::make($imagePath);
        $pathInfo = pathinfo($imagePath);
        $newPathImage = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $this->resizedFileName($imagePath, $width, $height);
        $img->fit($width, $height, function ($const) {
            $const->aspectRatio();
        })->save($newPathImage);

        return $newPathImage;
    }

    /**
     * Resize the given image to the specified width and height
     *
     * @param string $imagePath the path of the image
     * @param int    $dim       the new width or height
     * @param string $type      the width or height
     *
     * @return string the new path image
     *
     * @throws ImageException
     */
    public function imgResizeSingleDimension(string $imagePath, int $dim, string $type): string {
        list($imgWidth, $imgHeight) = getimagesize($imagePath);
        if ($type == 'height') {
            if ($imgHeight < $dim)
                throw new ImageException("The image is too small to resize ");

            $img = Image::make($imagePath);
            $pathInfo = pathinfo($imagePath);
            $newPathImage = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $this->resizedFileName($imagePath, $width = '', $dim);
            $img->fit(null, $dim, function ($const) {
                $const->aspectRatio();
            })->save($newPathImage);

            return $newPathImage;
        } elseif ($type == 'width') {
            if ($imgWidth < $dim)
                throw new ImageException("The image is too small to resize ");

            $img = Image::make($imagePath);
            $pathInfo = pathinfo($imagePath);
            $newPathImage = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $this->resizedFileName($imagePath, $dim, $height = 0);
            $img->fit($dim, null, function ($const) {
                $const->aspectRatio();
            })->save($newPathImage);

            return $newPathImage;
        }
    }

    /**
     * Helper to get the filename of a resized image
     *
     * @param string $imagePath absolute path of file
     * @param int    $width     the image width
     * @param int    $height    the image height
     *
     * @return string
     */
    public function resizedFileName(string $imagePath, int $width, int $height): string {
        $pathInfo = pathinfo($imagePath);
        if ($width == 0)
            return $pathInfo['filename'] . '_x' . $height . '.' . $pathInfo['extension'];
        elseif ($height == 0)
            return $pathInfo['filename'] . '_' . $width . 'x.' . $pathInfo['extension'];
        else
            return $pathInfo['filename'] . '_' . $width . 'x' . $height . '.' . $pathInfo['extension'];
    }

    /**
     * @param array $params the id of the media
     *
     */
    // TODO: make the test NOT use AWS and use a local filesystem
    public function _deleteImagesJob(array $params) {
        $geohubServiceProvider = app(GeohubServiceProvider::class);
        if (!isset($params['url']) || empty($params['url']))
            throw new MissingMandatoryParametersException('The parameter "url" is missing but required. The operation can not be completed');
        if (!isset($params['thumbnails']) || empty($params['thumbnails']))
            throw new MissingMandatoryParametersException('The parameter "thumbnails" is missing but required. The operation can not be completed');
        $thumbUrls = json_decode($params['thumbnails']);
        $awsPath = explode('https://' . config('filesystems.disks.s3.bucket') . '.s3.eu-central-1.amazonaws.com', $params['url']);
        $awsPathImage = $awsPath[1];

        try {
            Storage::disk('s3')->delete($awsPathImage);
            Log::info('Original Image deleted');
        } catch (Exception $e) {
            throw new Exception("Original Image cannot be deleted");
        }

        foreach ($thumbUrls as $thumb) {
            $thumbPath = explode('https://' . config('filesystems.disks.s3.bucket') . '.s3.eu-central-1.amazonaws.com', $thumb);
            $thumbPath = $thumbPath[1];
            try {
                Storage::disk('s3')->delete($thumbPath);
                Log::info('Resized ' . $thumbPath . 'Image deleted');
            } catch (Exception $e) {
                throw new Exception("Resize " . $thumbPath . "cannot be deleted");
            }
        }
    }
}
