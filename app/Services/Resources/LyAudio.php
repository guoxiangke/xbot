<?php

namespace App\Services\Resources;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use voku\helper\HtmlDomParser;
use App\Services\Resource;

final class LyAudio  extends Resource {
	public function __invoke($keyword) {
    //3位数关键字xxx
    // $offset = substr($oriKeyword, 3) ?: 0;
    $keyword = substr($keyword, 0, 3);
    if($keyword == 600){
        $content = "=====生活智慧=====\n【613】i-Radio爱广播\n【634】欢乐揪你耳\n【668】岁月正好\n【674】深度泛桌派\n【675】不孤单地球\n【678】阅读视界\n【610】星动一刻\n【612】书香园地\n【614】今夜心未眠\n【611】零点凡星\n【657】天使夜未眠\n=====关怀辅导=====\n【603】空中辅导\n【601】无限飞行号\n=====少儿家庭=====\n【660】我们的时间\n【606】亲情不断电\n【652】喜乐葡萄剧乐部\n【664】小羊圣经故事\n【659】爆米花\n【604】恋爱季节\n=====诗歌音乐=====\n【699】每当想起你\n【680】午的空间\n【616】长夜的牵引\n【623】齐来颂扬\n=====生命成长=====\n【698】馒头的对话\n【620】旷野吗哪\n【618】献上今天\n【619】拥抱每一天\n【624】施恩座前\n【640】这一刻，清心\n【628】空中崇拜\n【672】燃亮的一生\n=====圣经讲解=====\n【622】圣言盛宴\n【681】卢文心底话\n【679】经典讲台\n【677】穿越圣经（粤）\n【676】穿越圣经（普）\n【621】真道分解\n【629】善牧良言\n【654】与神同行\n【625】真理之光\n【664】小羊圣经故事\n【648】天路导向（普、英）\n【649】天路导向（粤、英）\n=====课程训练=====\n【646】晨曦讲座\n【641】良友圣经学院（启航课程）\n【642】良院本科1\n【643】良院本科2\n【644】良院进深1\n【645】良院进深2\n【647】良院专区\n【632】跟祢脚踪\n【671】良院讲台\n=====其他语言=====\n【650】恩典与真理（回族）\n【651】爱在人间（云南话）";
        return [
        	'type' => 'text',
        	'data' => ['content' => $content]
        ];
    }
    if($keyword>600 && $keyword<700){
        $map = ['ir'=>'613','hj'=>'634','ec'=>'668','pt'=>'674','wc'=>'675','bn'=>'678','hp'=>'610','bc'=>'612','rt'=>'614','sa'=>'611','ka'=>'657','cc'=>'603','ib'=>'601','ut'=>'660','up'=>'606','jvc'=>'652','pc'=>'659','se'=>'604','ty'=>'699','gf'=>'680','ws'=>'616','cw'=>'623','mn'=>'698','mw'=>'620','dy'=>'618','ee'=>'619','tg'=>'624','mp'=>'640','aw'=>'628','ls'=>'672','bs'=>'622','fh'=>'681','sc'=>'679','cttb'=>'677','ttb'=>'676','be'=>'621','yp'=>'629','it'=>'654','th'=>'625','cs'=>'664','wa'=>'648','cwa'=>'649','ds'=>'646','ltsnp'=>'641','ltsdp1'=>'642','ltsdp2'=>'643','ltshdp1'=>'644','ltshdp2'=>'645','vc'=>'647','dt'=>'632','vp'=>'671','gt'=>'650','ynf'=>'651',];
        if($code = array_search($keyword, $map)){
            $data = Cache::get($code, false);//cc
            if(!$data){
                $json = Http::get('https://open.ly.yongbuzhixi.com/api/program/'.$code)->json();
                $item = $json['data'][0];
                $data =[
                    "url" => $item['link'],
                    'title' => "【{$keyword}】".$item['program_name'].'-'.$item['play_at'],
                    'description' => $item['description'],
                ];
                // Carbon::tomorrow()->diffInSeconds(Carbon::now());
                Cache::put($code, $data, strtotime('tomorrow') - time());
            }
		        return [
		        	'type' => 'music',
		        	"data"=> $data,
		        ];
        }else{
		      return [
	        	'type' => 'text',
            "data"=> [
              "content"=> '此节目已停播/暂无此编号资源',
            ]
          ];
        }
    }
  }
}