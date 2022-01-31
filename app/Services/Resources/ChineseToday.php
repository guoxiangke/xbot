<?php

namespace App\Services\Resources;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use voku\helper\HtmlDomParser;

final class ChineseToday{
	public function __invoke($keyword)
	{
        if($keyword == "每日箴言"){
            $date = date('ymd');
            $cacheKey = "xbot.keyword.ChineseToday";
            $data = Cache::get($cacheKey, false);
            if(!$data){
                // http://chinesetodays.org/sites/default/files/devotion_audio/2017c/220127.mp3
                $response = Http::get("https://seekinggod.cn/ct{$date}");
                $html =$response->body();
                $htmlTmp = HtmlDomParser::str_get_html($html);
                $mp3 =  $htmlTmp->getElementByTagName('audio')->getAttribute('src');
                $title =  $htmlTmp->getElementByTagName('title')->text();

                $data =[
                    "url" => $mp3,
                    'title' => "【每日箴言】{$date}",
                    'description' => $title,
                ];
                Cache::put($cacheKey, $data, strtotime('tomorrow') - time());
            }
            return [
                'type' => 'music',
                "data"=> $data,
            ];
        }
	}
}
