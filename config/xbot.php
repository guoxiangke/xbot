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

    'silkDomain' => env('XBOT_SILK', 'silkWindowsTempExposeHttpWithPort'),
    'voiceDomain' => env('XBOT_VOICE', 'Upyun voice mp3 domain'),
    'fileDomain' => env('XBOT_FILE', 'WindowsFileExposeHttpWithPort'),
];