<?php

namespace App\Services;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Str;
use ReflectionClass;

class Resource
{
    // getResByKeyword
    public function __invoke($keyword){
        $paths = __DIR__.'/Resources';
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
    	return $res;
    }
}
