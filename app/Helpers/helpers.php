<?php
# https://laravel-news.com/creating-helpers
// "files": ["app/Helpers/helpers.php"]
# composer dump-autoload
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File; 


function xStringToJson($xmlString)
{ 
    return json_encode(simplexml_load_string($xmlString, null, LIBXML_NOCDATA));
}

function xStringToArray($xmlString)
{ 
    return json_decode(xStringToJson($xmlString), TRUE);
}

// $classes = getClassesList(app_path('Models'));
function getClassesList($dir)
{
    $classes = File::allFiles($dir);
    foreach ($classes as $class) {
        $class->classname = str_replace(
            [app_path(), '/', '.php'],
            ['App', '\\', ''],
            $class->getRealPath()
        );
    }

    return $classes;
}
