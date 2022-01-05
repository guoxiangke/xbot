<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WechatBot;
use App\Models\WechatBotContact;
use App\Models\WechatMessage;
use App\Models\WechatMessageVoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\Xbot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class XbotCallbackController extends Controller
{

    public function __invoke(Request $request, $token){

        $type = $request['type']??'null';
        $clientId = $request['client_id'];
        if(!($clientId && $type)) {
            Log::error(__CLASS__, [$clientId, __LINE__, $request->all(),'参数错误,非法调用，不存在type和client_id']);
            return response()->json(null);
        }
        $data = $request['data'];

        //windows机器.env配置都使用admin的不带任何权限token
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if(!($personalAccessToken && $personalAccessToken->tokenable_id == 1)){
            Log::error(__CLASS__, [$clientId, __LINE__, $request->all(), '参数Token错误, 请联系管理员']);
            return response()->json(null);
        }

        //////////////////////////一看到token，就知道在哪台机器上运行
        // ，然后根据token查询wechatBot表，查询绑定的wxid
        //通过token找Windows机器的地址
        $rootUser = User::firstOrFail();//请先创建一个user
        $tokens = $rootUser->getMeta('xbot.token', []);
        $address = $tokens[$token];
        //////////////////////////

        $cacheKey = $token . '.' . $clientId;
        // 1.获取到登陆二维码
        if($type == 'MT_RECV_QRCODE_MSG') {
            $qr =  $data['code'];
            Cache::put("xbots.{$cacheKey}.loginQr", $qr, 30);
            // TODO 前端刷新获取二维码  或使用 Broadcasting：https://laravel.com/docs/8.x/broadcasting
            Log::debug(__CLASS__, [$clientId, __LINE__, $clientId, '获取到登陆二维码', $cacheKey, $qr]);

            //如果登陆中！
            $wechatBot = WechatBot::where('token', $token)
                ->where('client_id', $clientId)
                ->first();
            if($wechatBot){
                $wechatBot->login_at = null;
                $wechatBot->client_id = null;
                $wechatBot->save();
                $wechatBot->setMeta('xbot', null);
            }
            return response()->json(null);
        }
        // 2.登陆成功 写入数据库
        if($type == 'MT_USER_LOGIN'){
            $wechatBot = WechatBot::where('wxid', $data['wxid'])->first();
            $wechatBot->login_at = now();
            $wechatBot->client_id = $clientId;
            $wechatBot->save();
            $wechatBot->setMeta('xbot', $data);
            Log::debug(__CLASS__, [$clientId, __LINE__, $clientId, '登陆成功']);
            return response()->json(null);
        }

        if($type == 'MT_USER_LOGOUT'){
            Log::debug(__CLASS__, [$clientId, __LINE__, 'MT_USER_LOGOUT']);
            $wechatBot = WechatBot::where('wxid', $data['wxid'])->first();
            $wechatBot->login_at = null;
            $wechatBot->client_id = null;
            $wechatBot->save();
            $wechatBot->setMeta('xbot', null);
            return response()->json(null);
        }

        //TODO 用户登陆出，$bot->login_at=null
            // 用户在手机上登出
            // 用户在Windows上登出
            // 在网页上点登出
            // 开发者调用登出

        // 忽略1小时以上的信息 60*60
        if(isset($data['timestamp']) && $data['timestamp']>0 &&  now()->timestamp - $data['timestamp'] > 60*60 ) {
            Log::debug(__CLASS__, [$clientId, __LINE__,now()->timestamp, $data['timestamp'], '忽略1小时以上的信息']);
            return response()->json(null);
        }

        //**********************DEBUG IGNORE BEGIN***********************************
        $ignoreHooks = [
            'MT_RECV_MINIAPP_MSG' => '小程序信息',
            'MT_RECV_LINK_MSG' => '公众号link消息',
            "MT_WX_WND_CHANGE_MSG"=>'',
            "MT_DEBUG_LOG" =>'调试信息',
            "MT_UNREAD_MSG_COUNT_CHANGE_MSG" => '未读消息',
            "MT_DATA_WXID_MSG" => '从网络获取信息',
            "MT_TALKER_CHANGE_MSG" => '客户端点击头像'
        ];
        if(in_array($type, array_keys($ignoreHooks))){
            return response()->json(null);
        }
        $ignoreRAW = [
            'MT_RECV_TEXT_MSG',
            'MT_RECV_OTHER_APP_MSG', //音乐消息🎵  "wx_sub_type":3, "wx_type":49
            'MT_DATA_FRIENDS_MSG',
            'MT_DATA_CHATROOMS_MSG',
            'MT_DATA_PUBLICS_MSG',
            'MT_RECV_PICTURE_MSG',
            'MT_DECRYPT_IMG_MSG',
            'MT_RECV_EMOJI_MSG',
            'MT_RECV_FILE_MSG',
            // 'MT_DATA_OWNER_MSG', // 获取到bot信息
        ];
        if(!in_array($type, $ignoreRAW)){
            Log::debug("CALLBACK-RAW-" . $type, [$request->all()]);
        }
        //**********************DEBUG IGNORE END***********************************

        //*********************************************************
        // 通过clientId 找到对应的wechatBot
        // 群消息中，没有Bot的wxid  "from_wxid":"xxx"  "to_wxid":"23887@chatroom"
        $wechatBot = WechatBot::where('token', $token)
            ->where('client_id', $clientId)
            ->first();
        //*********************************************************
        $botWxid = $data['to_wxid']??null;

        $content = ''; //写入 WechatMessage 的 content
        $isRoom = $data['room_wxid']??false; //群

        // 初始化 联系人数据
        $syncContactTypes = ['MT_DATA_FRIENDS_MSG', 'MT_DATA_CHATROOMS_MSG', 'MT_DATA_PUBLICS_MSG' ];
        if(in_array($type, $syncContactTypes)){
            $wechatBot->syncContacts($data, $type);
            Log::debug(__CLASS__, [$clientId, __LINE__, '获取联系人', $type]);
            return response()->json(null);
        }


        //??? 说明是被动响应的信息，丢弃，不然自己给自己聊天了！
        // if(!$wechatBot) {
        //     Log::debug(__CLASS__, [$clientId, __LINE__, $type, '被动响应的信息', '已丢弃']);
        //     return response()->json(null);
        // }
        if(!($wechatBot || $botWxid)){
            Log::error(__CLASS__, [$clientId, __LINE__, $request->all()]);
        }

        if(isset($data['room_wxid'])){
            if($wechatBot){
                if($data['from_wxid'] == $wechatBot->wxid) {
                    Log::debug(__CLASS__, [$clientId, __LINE__, '自己响应群消息,不继续执行了，即不发送给developer']);
                    return response()->json(null);
                }else{
                    // 接收到群消息！群消息里，没有wxid, from_wxid = 发送者，to_wxid=wx@room room_wxid=wx@room
                    Log::debug(__CLASS__, [$clientId, __LINE__, '接收到群消息']);
                    //go to next();
                }
            }else{
                Log::error(__CLASS__, [$clientId, __LINE__, $request->all(), '错误，找不到bot！']);
            }
        }else{
            if(isset($data['from_wxid']) && $data['from_wxid'] == $data['to_wxid']){
                Log::debug(__CLASS__, [$clientId, __LINE__, '自己发给自己消息，即不发送给develope' , $request->all()]);
                //因bot发的信息（通过关键词响应的信息）也要记录，所以继续走下去吧！不return了！
                // return response()->json(null);
            }
        }

        //************************************************
        $xbot = $wechatBot->xbot($clientId);
        //************************************************
        if(isset($data['raw_msg'])) $xml = xStringToArray($data['raw_msg']);
        if(isset($data['to_wxid']) && $data['to_wxid'] == "filehelper") {
            Log::debug(__CLASS__, [$clientId, __LINE__, '自己发给自己的filehelper消息，暂不处理！']);
            return response()->json(null);
        }

        // TODO 
            // MT_RECV_LINK_MSG 公众号消息

        //自动////自动////自动////自动////自动//
        //自动退款，如果数字不对
        // "des":"收到转账0.10元。如需收钱，请点此升级至最新版本",
        $switchOn = true; //需要用户可以在后台来改
        if($switchOn && $type == 'MT_RECV_WCPAY_MSG'){
            // "feedesc":"￥0.10",
            // substr('￥0.10',3) + 1 = 1.1 x 100 = 110分
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

        // ✅ 搜索用户信息后的callback，主动+好友
        $switchOn = true;
        if ($switchOn && $type == 'MT_SEARCH_CONTACT_MSG') {
            Log::info(__CLASS__, ['MT_SEARCH_CONTACT_MSG','主动+好友', $data['nickname'], $data['search']]);
            $remark = "朋友介绍"; //todo remark settings in FE
            $xbot->addFriendBySearchCallback($data['v1'], $data['v2'], $remark);
            return response()->json(null);
        }

        // ✅ 自动同意好友请求
        $switchOn = true;
        if($switchOn && $type == 'MT_RECV_FRIEND_MSG'){
            //TODO  get $scene, $v1, $v2 from xml!
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
        // 监控上传文件夹2 C:\Users\Administrator\AppData\Local\Temp\ =》 /xbot/mp3/
        if($type == 'MT_RECV_VOICE_MSG'){
            $msgid = $data['msgid'];
            Log::debug(__CLASS__, [$clientId, __LINE__, $clientId, $type, '语音消息']);
            // TODO 
            // 1. 自动同步到 xbot/silk/wxs1692.tmp
            // 2. 自动触发 转换mp3动作  xbot/mp3/$data['msgid'].mp3
            $content = "/xbot/mp3/{$data['msgid']}.mp3";
            $xbot->toVoiceText($msgid);
        }
        // ✅ 提取转成的文字
        // TODO 下面的post要带上 转换后的文字
        if($type == 'MT_TRANS_VOICE_MSG'){
            WechatMessageVoice::create([
                'msgid' => $data['msgid'],
                'content' => $data['text'],
            ]);
            Log::debug(__CLASS__, [$clientId, __LINE__, $type, '语音消息转文本', $data]);
            return response()->json(null);
        }
        // ✅ 收到gif表情
        if($type == 'MT_RECV_EMOJI_MSG'){
            $content = $xml['emoji']['@attributes']['cdnurl'];
        }
        // ✅ 收到图片
        if($type == 'MT_RECV_PICTURE_MSG'){
            $src_file = $data['image'];
            $msgid = $data['msgid'];
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
            $content = "/xbot/images/{$md5}.png"; // config('xbot.upyun').
        }
        // ✅  文件消息
        // 监控上传文件夹3 C:\Users\Administrator\Documents\WeChat Files\  =》 /xbot/file/
        if($type == 'MT_RECV_FILE_MSG'){
            $file = str_replace('C:\\Users\\Administrator\\Documents\\WeChat Files\\','/xbot/file/', $data['file']);
            $content =  str_replace('\\','/', $file);
            Log::debug(__CLASS__, [$clientId, __LINE__, $clientId, $type, '文件消息', $data['file']]);
        }

        if($type == 'MT_RECV_TEXT_MSG'){ //接收到 个人/群 文本消息
            $content = $data['msg'];
            $replyTo = $data['from_wxid']; //消息发送者
            if($isRoom) $replyTo = $data['room_wxid'];
            if($data['from_wxid'] == $wechatBot->wxid) $replyTo = $data['to_wxid']; //自己给别人聊天时，发关键词 响应信息
            //TODO 彩蛋:谁在线，在线时长！
            if($content=='whoami'){
                $time = $wechatBot->login_at->diffForHumans();
                $text = "已登陆 $time\n时间: {$wechatBot->login_at}\n设备ID: {$clientId}\n用户: {$wechatBot->user->name}";
                $xbot->sendText($replyTo, $text);
            }
        }


        // Log::debug(__CLASS__, [$clientId, __LINE__, '开发者选项', $request->all()]);
        // return response()->json(null);
        // 把接收的消息写入 WechatMessage
        $recordWechatMessageTypes = [
            'MT_RECV_TEXT_MSG', 
            'MT_RECV_VOICE_MSG',
            'MT_RECV_EMOJI_MSG',
            'MT_RECV_PICTURE_MSG',
            'MT_RECV_FILE_MSG',
        ];
        if(in_array($type,$recordWechatMessageTypes)) {
            $fromWxid = $data['from_wxid'];
            $conversationWxid = $data['from_wxid'];
            // 被动响应的信息+主动回复给filehelper的信息
            if($data['from_wxid'] == $wechatBot->wxid){
                $fromId = null;
                $conversationWxid = $data['to_wxid'];
            }else{
                $from = WechatBotContact::where('wxid', $fromWxid)->first();
                $fromId = $from->id;
            }
            //如果是群，别人发的信息
            if($isRoom){
                $conversationWxid = $data['room_wxid'];
            }
            $conversation = WechatBotContact::where('wxid', $conversationWxid)->first();
            WechatMessage::create([
                'type' => array_search($type, WechatMessage::TYPES), // 1文本
                'wechat_bot_id' => $wechatBot->id,
                'from' => $fromId, // 消息发送者:Null为bot发送的
                'conversation' => $conversation->id, //群/个人
                'content' => $content,
                'msgid' => $data['msgid'],
            ]);
        }
        // TODO: 从数据库中获取自定义的callback
        // 不知道为什么暂时发送给本laravel却卡死！
        // $callback = 'http://xxx.yy.com:xxx/api/xxx';
        // $http = new Http();
        // Http::post($callback, $content); //测试连通性，或放到队列中去执行！
        Log::debug(__CLASS__, [$clientId, __LINE__, '开发者选项', $content]);
        return response()->json(null);
    }
}
