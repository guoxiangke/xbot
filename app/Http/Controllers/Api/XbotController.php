<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client as Http; // L6
use App\Services\Xbot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class XbotController extends Controller
{
    public function callback(Request $request, $address){
        $type = $request['type']??'null';
        $clientId = $request['client_id'];
        if(!($clientId && $type)) {
            Log::debug(__CLASS__, [$request->all(),'❌参数错误']);
            return response()->json(null);
        }
        $data = $request['data'];

        // TODO
        //TODO bot信息 写入数据库，对应Bot model 
        $address = base64_decode($address); // http://x.2.2.1:123
        $cacheKey = $address . '.' . $clientId;
        // TODO cache不可靠！需要写入数据库来缓存并查询 client_id 和 bot的对应关系
        $bots = Cache::get('xbots', []); //除非手动清空了缓存，那缓存就不可靠？then 写入数据库
        $botWxid = $bots[$cacheKey]??'null'; // 肯定有值？
        $xGroup = config('xbot.xGroup') ;//// xbot群
        $filehelper = 'filehelper'; //文件传输助手

        // 1.获取到登陆二维码 写入数据库
        if($type == 'MT_RECV_QRCODE_MSG') {
            $qr =  $data['code'];
            Cache::put("xbots.{$cacheKey}.loginQr", $qr, 30);
            // TODO 前端刷新获取二维码
            // 或使用 Broadcasting：https://laravel.com/docs/8.x/broadcasting 
            Log::debug('获取到登陆二维码', [$cacheKey, $qr]);
            return response()->json(null);
        }
        // 2.登陆成功 写入数据库
        if($type == 'MT_USER_LOGIN'){
            // $bot->login_at=now()
            //TODO： Cache:: client_id:wxboId
            $bots = Cache::get('xbots', []);
            $bots[$cacheKey] = $data['wxid'];
            Cache::put('xbots', $bots);
            return response()->json(null);
        }
        //TODO 用户登陆出，$bot->login_at=null
            // 用户在手机上登出
            // 用户在Windows上登出
            // 在网页上点登出
            // 开发者调用登出


        if(true){
            $ignoreHooks = [
                "MT_UNREAD_MSG_COUNT_CHANGE_MSG" => '未读消息',
                "MT_DATA_WXID_MSG" => '从网络获取信息',
                "MT_TALKER_CHANGE_MSG" => '客户端点击头像'
            ];
            if(in_array($type, array_keys($ignoreHooks))){ //未读消息
                // Log::debug('INGOREHOOK', [ $type, $ignoreHooks[$type]]);
                return response()->json(null);
            }
            // MT_RECV_OTHER_APP_MSG
                //音乐消息🎵  "wx_sub_type":3, "wx_type":49
            $ignoreRAW = ['MT_RECV_TEXT_MSG','MT_RECV_OTHER_APP_MSG'];
            if(!in_array($type, $ignoreRAW)){
                Log::debug("CALLBACK-RAW-" . $type, [$botWxid, $request->all()]);
            }
        }
        // 忽略所有 自己给自己发的信息
        if(($data['from_wxid']??null) == $botWxid){
            return response()->json(null);
        }
        //************************************************
        $xbot = new Xbot($clientId, $botWxid, $address);
        //************************************************

        //自动////自动////自动////自动////自动//
        //自动退款，如果数字不对
        // "des":"收到转账0.10元。如需收钱，请点此升级至最新版本",
        $switchOn = true; //需要用户可以在后台来改
        if($switchOn && $type == 'MT_RECV_WCPAY_MSG'){
            // "feedesc":"￥0.10", 
            // substr('￥0.10',3) + 1 = 1.1 x 100 = 110分
            $xml = xStringToArray($data['raw_msg']);
            $transferid = $xml['appmsg']['wcpayinfo']['transferid'];
            $amount = $xml['appmsg']['wcpayinfo']['feedesc'];
            $amount = substr($amount, 3) * 100;
            Log::debug('MT_RECV_WCPAY_MSG', ['微信转账', $transferid, $amount]);
            //TODO 只收 1 分钱，其他退回
            if($amount == 1) {
                $xbot->autoAcceptTranster($transferid);
            }else{
                $xbot->refund($transferid);
            }
            return response()->json(null);
        }

        // MT_RECV_EMOJI_MSG

        // ✅ 搜索用户信息后的callback，主动+好友
        $switchOn = true;
        if ($switchOn && $type == 'MT_SEARCH_CONTACT_MSG') {
            Log::info(__CLASS__, [$botWxid, 'MT_SEARCH_CONTACT_MSG','主动+好友', $data['nickname'], $data['search']]);
            $xbot->addFriendBySearchCallback($data['v1'], $data['v2']);
            return response()->json(null);
        }

        // ✅ 自动同意好友请求
        $switchOn = true;
        if($switchOn && $type == 'MT_RECV_FRIEND_MSG'){
            //TODO  get $scene, $v1, $v2 from xml!
            $xml = xStringToArray($data['raw_msg']);
            $attributes = $xml['@attributes'];

            $v3 = $attributes['encryptusername'];
            $v4 = $attributes['ticket'];
            $scene = $attributes['scene'];//3 14 ;
            $xbot->agreenFriend($scene, $v3, $v4);

            $fromnickname = $attributes['fromnickname'];
            $content = $attributes['content'];
            Log::debug('MT_RECV_FRIEND_MSG', ["已自动同意{$fromnickname}的好友请求:{$content}"]);
            return response()->json(null);
        }

        // ✅ 收到语音消息，即刻调用转文字
        if($type == 'MT_RECV_VOICE_MSG'){
            $msgid = $data['msgid'];
            Log::debug(__CLASS__, [$type, '语音消息']);
            $xbot->toVoiceText($msgid);
            return response()->json(null);
        }

        $sendToDevelop = [];
        // ✅ 提取转成的文字
        // TODO 下面的post要带上 转换后的文字
        if($type == 'MT_TRANS_VOICE_MSG'){
            $msgid = $data['msgid'];
            Log::debug(__CLASS__, [$type, '语音消息转文本', $data['text']]);
            $sendToDevelop = [
                'type' => 'vioce',
                'text' => $data['text'],
            ];
        }
        

        // 收到图片/发送图片消息的CALLBACK
        if($type == 'MT_RECV_PICTURE_MSG'){
            $src_file = $data['image'];
            $msgid = $data['msgid'];
            $xml = xStringToArray($data['raw_msg']);
            $size = $xml['img']['@attributes']['length'];
            if(isset($xml['img']['@attributes']['md5'])){
                $md5 = $xml['img']['@attributes']['md5'];
                $dest_file = "C:\\Users\\Public\\Pictures\\{$md5}.png";
                // wait for 2 seconds 2 000 000
                // usleep(500000);
                sleep(1);
                // $size = $xml['img']['@attributes']['length']??$xml['img']['@attributes']['hdlength']??'7765';
                $res = $xbot->getImage($src_file, $dest_file, $size);
            }
            // else{ //TODO test 主动发送的图片回调！
            //     Log::debug(__CLASS__, [$type, '主动发送的图片回调！','INGORE', '不再二次存储', $msgid, $length]);
            // }

            $sendToDevelop = [
                'type' => 'image',
                'url' => config('xbot.upyun')."/xbot/images/{$md5}.png",
            ];
            Log::debug(__CLASS__, [$type, $sendToDevelop]);
        }

        if($type == 'MT_RECV_TEXT_MSG'){ //接收到 个人/群 文本消息
            $msg = $data['msg'];
            $replyTo = $data['from_wxid']; //消息发送者
            $isRoom = false;
            if(isset($data['room_wxid'])){//群
                $replyTo = $data['room_wxid'];
                $isRoom = true;
            }

            //TODO 彩蛋:谁在线，在线时长！
            if($msg=='whoami'){
                $xbot->sendText("I am active！\n" .$botWxid, $replyTo);
                return response()->json(null);
            }
            if($msg=='quit'){
                $xbot->quit();
                return response()->json(null);
            }

            // no return!
            $sendToDevelop = [
                'type' => 'text',
                'text' => $msg,
                'from_wxid' => $replyTo,
                'is_room' => $isRoom,
            ];
        }
        // TODO: 从数据库中获取自定义的callback
        // $callback = 'http://xxx.yy.com:xxx/api/xxx';
        // $http = new Http();
        // $http->post($callback, ['json' => $sendToDevelop]);
        return response()->json(null);
    }
}
