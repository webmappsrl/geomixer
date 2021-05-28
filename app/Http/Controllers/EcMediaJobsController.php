<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class EcMediaJobsController extends Controller {
    public function uploadEcMediaImage(): JsonResponse {
        $apiUrl = explode("/", request()->path());
        $id = $apiUrl[4];

        if (!Storage::exists('ec_media/' . $id))
            return response()->json('Element does not exists', 404);

        Storage::disk('s3')->put('EcMedia/' . $id, file_get_contents(Storage::disk('local')->path('ec_media/' . $id)));

        return response()->json('Upload Completed');
    }
}
