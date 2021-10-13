<?php

return [
    'use_local_storage' => env('USE_LOCAL_STORAGE', false),
    'raster_tiles_path' => env('RASTER_TILES_PATH'),
    'node_executable' => env('NODE_EXECUTABLE'),
    'hoqu' => [
        'jobs_supported' => env('GEOMIXER_HOQU_SERVER_JOBS_SUPPORTED'),
        'jobs_not_supported' => env('GEOMIXER_HOQU_SERVER_JOBS_NOT_SUPPORTED'),
        'mbtiles' => [
            'zoom_levels' => [
                2 => [
                    'zooms' => 5,
                    'x' => [2, 2],
                    'y' => [1, 1]
                ],
                7 => [
                    'zooms' => 4,
                    'x' => [66, 70],
                    'y' => [44, 50]
                ],
                11 => [
                    'zooms' => 4,
                    'x' => [1061, 1129],
                    'y' => [719, 808]
                ],
                15 => [
                    'zooms' => 2,
                    'x' => [17112, 18070],
                    'y' => [11512, 12938]
                ]
            ]
        ]
    ],
    'ec_media' => [
        'thumbnail_sizes' => [
            ['width' => 108, 'height' => 148],
            ['width' => 108, 'height' => 137],
            ['width' => 150, 'height' => 150],
            ['width' => 225, 'height' => 100],
            ['width' => 118, 'height' => 138],
            ['width' => 108, 'height' => 139],
            ['width' => 118, 'height' => 117],
            ['width' => 335, 'height' => 250],
            ['width' => 400, 'height' => 200],
            ['width' => 1440, 'height' => 500],
            ['width' => 1920, 'height' => 0],
            ['width' => 250, 'height' => 150]
        ]
    ]
];
