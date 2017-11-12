<?php

return [
    'watermark_path' => storage_path('app/watermark.png'),

    'formats' => [
        [
            'name' => 'normal',
            'width' => 700,
            'ratio' => 16/9,
            'back_color' => '#fafafa',
            'format_encoding' => 'jpg',
        ]
    ],

    'service' => [
        'domains' => [
            env('PICS_MANAGER_STATIC1'),
            env('PICS_MANAGER_STATIC2'),
        ],
        'urls' => [
            'routeGetMd5' => '/private/getmd5',
            'routeCancelMd5' => '/private/cancelmd5',
            'routeSavePicture' => '/private/savepicture',
            'routeGetInfos' => '/private/infos',
            'routeDelete' => '/private'
        ]
    ],

];
