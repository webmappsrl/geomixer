<?php

return [

    'base_url' => env('HOQU_BASE_URL', 'https://hoqu.webmapp.it'),

    'server_id' => env('HOQU_SERVER_ID', 'geomixer'),

    'geohub_domain' => env('GEOHUB_DOMAIN', 'geohub.webmapp.it'),

    'token' => [
        'pull' => env('HOQU_PULL_TOKEN', null),
    ]
];
