<?php

return [
    'use_local_storage' => env('USE_LOCAL_STORAGE', false),
    'raster_tiles_path' => env('RASTER_TILES_PATH'),
    'hoqu_jobs_supported' => env('GEOMIXER_HOQU_SERVER_JOBS_SUPPORTED'),
    'hoqu_jobs_not_supported' => env('GEOMIXER_HOQU_SERVER_JOBS_NOT_SUPPORTED')
];
