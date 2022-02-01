<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'license' => env('XBOT_LICENSE', '=='),
    'xGroup' => env('XBOT_GROUP', 'xxx@chatroom'),
    'endpoint' => env('XBOT_ENDPOINT', 'http://localhost/api'),
    'resource_endpoint' => env('XBOT_RESOURCE_ENDPOINT', 'http://localhost/api/resources/'),
];