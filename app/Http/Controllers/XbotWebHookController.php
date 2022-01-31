<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Resource;

class XbotWebHookController extends Controller
{
    public function __invoke(Request $request, $token){
	    $keyword = $request['content'];
	    $xbotEndPoint = config('xbot.endpoint');
	    $action = '/wechat/send';
	    // http://localhost/api/wechat/send
	    $resource = app("App\Services\Resource");
	    $res = $resource->__invoke($keyword);
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
