<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\Resource;
use ReflectionClass;

class XbotWebHookController extends Controller
{
    public function __invoke(Request $request, $token){
	    $keyword = $request['content'];
	    $xbotEndPoint = config('xbot.endpoint');
	    $action = '/wechat/send';
	    // http://localhost/api/wechat/send
	    
	    $paths = __DIR__.'/../../Services/Resources';
	    $namespace = app()->getNamespace();
	    foreach ((new Finder)->in($paths)->files() as $file) {
          $resource = $namespace.str_replace(
              ['/', '.php'],
              ['\\', ''],
              Str::after($file->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
          );

          if (is_subclass_of($resource, Resource::class) &&
              ! (new ReflectionClass($resource))->isAbstract()) {
				$isEnable = true; // TODO weight 
			    if($isEnable){
			        $resource = app($resource);
			        $res = $resource->__invoke($keyword);
			        if(!is_null($res)) break;
			    }
          }
		}
		// 最后一步
		if($res){
	        Http::withToken($token)
	            ->post($xbotEndPoint . $action, [
	                "type"=> $res['type'],
	                "to"=> $request['who'],
	                "data"=> $res['data'],
	            ]);
	    }
	    // TODO finalReply
    }
}
