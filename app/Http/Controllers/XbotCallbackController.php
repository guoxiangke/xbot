<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WechatBot;
use App\Models\WechatContact;
use App\Models\WechatBotContact;
use App\Models\WechatMessage;
use App\Models\WechatMessageFile;
use App\Models\WechatMessageVoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\Xbot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Jobs\SilkConvertQueue;

class XbotCallbackController extends Controller
{

    public function __invoke(Request $request, $token){
        $type = $request['type']??false; // {"data":{"error":"参数错误"},"type":null,"client_id":1}
        $clientId = $request['client_id']??false;
        if(!($clientId && $type)){
            Log::error(__CLASS__, [$clientId, __LINE__, $request->all(), '参数错误']);
            return response()->json(null);
        }
        $data = $request['data'];

        //windows机器.env配置都使用admin的不带任何权限token
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if(!($personalAccessToken && $personalAccessToken->tokenable_id == 1)){
            Log::error(__CLASS__, [$clientId, __LINE__, $request->all(), '参数Token错误, 请联系管理员']);
            return response()->json(null);
        }

        $cacheKey = $token;
        // 1.获取到登陆二维码
        // 缓存以供前端调用扫码（2个client同一个id，如果已登陆的，不显示二维码！）
        if($type == 'MT_RECV_QRCODE_MSG') {
            $qr = [
                'qr' => $data['code'],
                'client_id' => $clientId,
            ];
            $qrPool = Cache::get("xbots.{$cacheKey}.qrPool", []);
            // 一台机器，多个客户端，使用二维码池, 池子大小==client数量，接收到1个新的，就把旧的1个弹出去
            // array_pop($qrPool);
            array_unshift($qrPool, $qr);
            Cache::put("xbots.{$cacheKey}.qrPool", $qrPool);
            // 前端刷新获取二维码总是使用第一个QR，登陆成功，则弹出对于clientId的QR
            // TODO 或使用 Broadcasting：https://laravel.com/docs/8.x/broadcasting
            Log::debug(__CLASS__, [$clientId, __LINE__, $clientId, '获取到登陆二维码，已压入qrPool', $cacheKey, $qr]);

            //如果登陆中！
            $wechatBot = WechatBot::where('token', $token)
                ->where('client_id', $clientId)
                ->first();
            if($wechatBot){
                $wechatBot->login_at = null;
                $wechatBot->client_id = null;
                $wechatBot->save();
            }
            return response()->json(null);
        }
        // 2.登陆成功 写入数据库
        if($type == 'MT_USER_LOGIN'){
            // 登陆成功，则弹出对于clientId的所有 QR
            $qrPool = Cache::get("xbots.{$cacheKey}.qrPool", []);
            // $key = array_search($clientId, array_column($qrPool, 'clientId'));
            foreach ($qrPool as $key => $value) {
                if($value['client_id'] == $clientId){
                    unset($qrPool[$key]);
                }
            }
            Cache::set("xbots.{$cacheKey}.qrPool", $qrPool);
            Log::debug(__CLASS__, [$clientId, __LINE__, $data['nickname'], '登陆成功','，已弹出qrPool']);

            $wechatBot = WechatBot::where('wxid', $data['wxid'])->first();
            $wechatBot->login_at = now();
            $wechatBot->is_live_at = now();
            $wechatBot->client_id = $clientId;
            $wechatBot->save();
            $data['avatar'] = str_replace('http://','https://', $data['avatar']);
            $wechatBot->setMeta('xbot', $data);

            Log::debug(__CLASS__, [$clientId, __LINE__, $data['nickname'], '下面执行初始化']);
            $wechatBot->init();
            return response()->json(null);
        }

        if($type == 'MT_USER_LOGOUT'){
            Log::debug(__CLASS__, [$clientId, __LINE__, $data['wxid'], 'MT_USER_LOGOUT']);
            $wechatBot = WechatBot::where('wxid', $data['wxid'])->first();
            $wechatBot->login_at = null;
            $wechatBot->is_live_at = null;
            $wechatBot->client_id = null;
            $wechatBot->save();
            return response()->json(null);
        }
        // MT_DATA_OWNER_MSG
        if($type == 'MT_DATA_OWNER_MSG') {
            $wechatBot = WechatBot::where('wxid', $data['wxid'])->first();
            // 程序崩溃时，login_at 还在，咋办？
            $wechatBot->update(['is_live_at'=>now()]);
            $data['avatar'] = str_replace('http://','https://', $data['avatar']);
            $wechatBot->setMeta('xbot', $data); //account avatar nickname wxid
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
            'MT_ROOM_ADD_MEMBER_NOTIFY_MSG',
            'MT_ROOM_DEL_MEMBER_NOTIFY_MSG',
            'MT_CONTACT_ADD_NOITFY_MSG', // 同意好友请求 发送 欢迎信息
            'MT_ADD_FRIEND_MSG', // 主动+好友
            'MT_SEARCH_CONTACT_MSG', //添加好友
            'MT_RECV_VOICE_MSG',
            // 'MT_RECV_FRIEND_MSG',
            'MT_RECV_SYSTEM_MSG', // 
            'MT_RECV_TEXT_MSG',
            'MT_RECV_OTHER_APP_MSG', //音乐消息🎵  "wx_sub_type":3, "wx_type":49
            'MT_DATA_FRIENDS_MSG',
            'MT_DATA_CHATROOMS_MSG',
            'MT_DATA_PUBLICS_MSG',
            'MT_RECV_PICTURE_MSG',
            'MT_RECV_EMOJI_MSG',
            'MT_RECV_FILE_MSG',
            'MT_DECRYPT_IMG_MSG',
            'MT_DECRYPT_IMG_MSG_SUCCESS',
            'MT_DATA_OWNER_MSG', // 获取到bot信息
            'MT_RECV_VIDEO_MSG',
        ];
        if(!in_array($type, $ignoreRAW)){
            Log::debug(__CLASS__, [$clientId, __LINE__, $type, $request->all()]);
        }
        //**********************DEBUG IGNORE END***********************************

        //*********************************************************
        // 通过clientId 找到对应的wechatBot
        // 群消息中，没有Bot的wxid  "from_wxid":"xxx"  "to_wxid":"23887@chatroom"
        $wechatBot = WechatBot::where('token', $token)
            ->where('client_id', $clientId)
            ->first();
        if(!$wechatBot) {
            Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, ' 不存在wechatBot？设备已下线！']);
            return response()->json(null);
        }
        //*********************************************************
        $botWxid = $data['to_wxid']??null;

        $content = ''; //写入 WechatMessage 的 content
        $isRoom = $data['room_wxid']??false; //群

        $config = $wechatBot->getMeta('xbot.config', [
            'isAutoWcpay' => false, // MT_RECV_WCPAY_MSG
            'isAutoAgree' => false, // 自动同意好友请求
            'isWelcome' => false,
            'weclomeMsg' => 'hi',
            'isListenRoom' => false,
            'isListenRoomAll' => false,
        ]);

        // 初始化 联系人数据
        $syncContactTypes = ['MT_DATA_FRIENDS_MSG', 'MT_DATA_CHATROOMS_MSG', 'MT_DATA_PUBLICS_MSG' ];
        if(in_array($type, $syncContactTypes)){
            $wechatBot->syncContacts($data, $type);
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, '获取联系人', $type]);
            return response()->json(null);
        }
        // MT_ROOM_ADD_MEMBER_NOTIFY_MSG 新人入群
        // MT_ROOM_CREATE_NOTIFY_MSG 被拉入群
        if($type == 'MT_ROOM_ADD_MEMBER_NOTIFY_MSG' || $type == 'MT_ROOM_CREATE_NOTIFY_MSG'){
            $groupWxid = $data['room_wxid'];
            $gBotContact = WechatBotContact::withTrashed()
                ->where('wechat_bot_id', $wechatBot->id)
                ->firstWhere('wxid', $groupWxid);
            // 
            if(!$gBotContact){
                // 群 不存在的话，创建该群
                $groupContact = [
                    'type' => 2,//群
                    'wxid' => $groupWxid,
                ];
                ($contact = WechatContact::firstWhere('wxid', $gBotContact))
                    ? $contact->update($groupContact) // 更新资料
                    : $contact = WechatContact::create($groupContact);
                $attachs[$contact->id] = [
                    'type' => 2, //群陌生人
                    'wxid' => $contact->wxid,
                    'remark' => $member['nickname']??$contact->wxid,
                    'seat_user_id' => $wechatBot->user_id, //默认坐席为bot管理员
                ];
                $wechatBot->contacts()->syncWithoutDetaching($attachs);
                Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $gBotContact->nickname, 'new group']);
            }else{
                if($gBotContact->trashed()){
                    $gBotContact->restore();
                    Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $gBotContact->nickname, 'restored']);
                }
            }
            
            // 如果联系人不存在，则创建在wechatContact 和 wechatBotContact
            // 1.假设群已经存在，只需要处理新加入的群成员
            $attachs = [];
            foreach ($data['member_list'] as $member) {
                ($contact = WechatContact::firstWhere('wxid', $member['wxid']))
                    ? $contact->update($member) // 更新资料
                    : $contact = WechatContact::create($member);
                Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $contact->nickname, '添加好友到群，好友入库']);
                $memberBotContact = WechatBotContact::withTrashed()
                    ->where('wechat_bot_id', $wechatBot->id)
                    ->firstWhere('wxid', $member['wxid']);

                if(!$memberBotContact){
                    $type = 3; //群陌生人
                    $attachs[$contact->id] = [
                        'type' => $type, //群陌生人
                        'wxid' => $contact->wxid,
                        'remark' => $member['nickname']??$contact->wxid,
                        'seat_user_id' => $wechatBot->user_id, //默认坐席为bot管理员
                    ];
                    Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $contact->nickname, '添加好友到群，群陌生人']);
                }else{
                    // softDelete->restore()
                    if($memberBotContact->trashed()){
                        $memberBotContact->restore();
                    }
                    //如果已经存在了，说明可能之前是好友，然后被加到这个群了
                    Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $contact->nickname, '添加好友到群，群好友']);
                }
            }
            $wechatBot->contacts()->syncWithoutDetaching($attachs);
            return response()->json(null);
        }
        // # bot/群成员 被踢出群
        // 群成员 被踢出群 不做任何操作
        if($type == 'MT_ROOM_DEL_MEMBER_NOTIFY_MSG'){
            // 如果是bot
            $isBotRemovedFromGroup = false;
            foreach ($data['member_list'] as $member) {
                if($member['wxid'] == $wechatBot->wxid){
                    $isBotRemovedFromGroup = true;
                }else{ //其他人 退群/被移出群
                    // 1.找到这个 陌生人id
                    $gBotContact = WechatBotContact::withTrashed()
                        ->where('wechat_bot_id', $wechatBot->id)
                        ->firstWhere('wxid', $member['wxid']);
                    // $content = "{$member['nickname']}被出群了";
                    // 2.群消息不变，他发的都删！
                    WechatMessage::query()
                        ->where('from', $gBotContact->id)
                        ->delete();
                    $gBotContact->delete();
                    Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $gBotContact->nickname, $gBotContact->id, '！bot被出群了！消息删除了']);
                }
            }
            //2. 删除 wechat_bot_contacts
            //1. 删除 messages
            if($isBotRemovedFromGroup) {
                $groupWxid = $data['room_wxid'];
                $gBotContact = WechatBotContact::withTrashed()
                    ->where('wechat_bot_id', $wechatBot->id)
                    ->firstWhere('wxid', $groupWxid);
                    // ->where('type', 2) 群，一定是2
                    // firstWhere /get 一定有一个
                WechatMessage::query()
                    ->where('conversation', $gBotContact->id)
                    ->delete();
                $gBotContact->delete();
                Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $gBotContact->nickname, $gBotContact->id, 'bot被出群了！消息删除了']);
            }
        }


        //??? 说明是被动响应的信息，丢弃，不然自己给自己聊天了！
        // if(!$wechatBot) {
        //     Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, '被动响应的信息', '已丢弃']);
        //     return response()->json(null);
        // }
        if(!($wechatBot || $botWxid)){
            Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $request->all()]);
        }

        if($isRoom){
            if(isset($data['from_wxid']) && $data['from_wxid'] == $wechatBot->wxid) {
                Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, '自己响应的群消息']);
                // return response()->json(null);
            }else{
                // 接收到群消息！群消息里，没有wxid, from_wxid = 发送者，to_wxid=wx@room room_wxid=wx@room
                Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, '接收到群消息']);
                if(!$config['isListenRoom']){
                    Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, '终止执行,没有监听群消息']);
                    return response()->json(null);
                }
                if(!$config['isListenRoomAll']){
                    Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, '终止执行，没有监听所有群消息']);
                    return response()->json(null);
                }
                //go to next(); //TODO 如果监听群消息，但全部监听？
            }
        }
        // else{
        //     if(isset($data['from_wxid']) && $data['from_wxid'] == $data['to_wxid']){
        //         Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, '自己发给自己消息，即不发送给develope' , $request->all()]);
        //         //因bot发的信息（通过关键词响应的信息）也要记录，所以继续走下去吧！不return了！
        //         // return response()->json(null);
        //     }
        // }

        //************************************************
        $xbot = $wechatBot->xbot($clientId);
        //************************************************
        if(isset($data['raw_msg'])){
            $tmpData = $data['raw_msg'];
            if(Str::startsWith($tmpData, '<?xml ') || Str::startsWith($tmpData, '<msg')) {
                 $xml = xStringToArray($tmpData);
            }else{
                Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, 'raw data not xml']);
                // MT_RECV_SYSTEM_MSG "raw_msg":"你已添加了天空蔚蓝，现在可以开始聊天了。"
                $data['msg'] = $data['raw_msg'];
                $content = $data['msg'];
            }
        }
        if(isset($data['to_wxid']) && $data['to_wxid'] == "filehelper") {
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, '自己发给自己的filehelper消息，暂不处理！']);
            return response()->json(null);
        }

        // TODO
            // MT_RECV_LINK_MSG 公众号消息

        //自动////自动////自动////自动////自动//
        //自动退款，如果数字不对
        // "des":"收到转账0.10元。如需收钱，请点此升级至最新版本",
        $switchOn = $config['isAutoWcpay'];
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
        if ($type == 'MT_SEARCH_CONTACT_MSG') {
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, '主动+好友', $data['search']]);
            $remark = "朋友介绍"; //todo remark settings in FE
            if(isset($data['v1']) && isset($data['v2'])){
                $xbot->addFriendBySearchCallback($data['v1'], $data['v2'], $remark);
            }else{
                $xbot->getRooms(); //更新群
                Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, '主动+好友', $data]);
            }
            return response()->json(null);
        }

        // ✅ 收到好友请求
        $switchOn = $config['isAutoAgree'];
        if($switchOn && $type == 'MT_RECV_FRIEND_MSG'){
            //TODO  get $scene, $v1, $v2 from xml!
            $attributes = $xml['@attributes'];

            $v3 = $attributes['encryptusername'];
            $v4 = $attributes['ticket'];
            $scene = $attributes['scene'];//3: 14: 6:拉黑用户再次请求;
            $xbot->agreenFriend($scene, $v3, $v4);

            $fromnickname = $attributes['fromnickname'];
            $content = $attributes['content'];
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, "收到{$fromnickname}的好友请求:{$content}"]);
            return response()->json(null);
        }

        // ✅ 手动同意好友请求 发送 欢迎信息
        $switchOn = $config['isWelcome'];
        if($switchOn && $type == 'MT_CONTACT_ADD_NOITFY_MSG'){
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, '同意好友请求 发送 欢迎信息']);
            $xbot->sendText($data['wxid'], $config['weclomeMsg']);
            // 写入数据库
            $wechatBotContact = WechatBotContact::query()
                ->withTrashed()
                ->where('wxid', $data['wxid'])
                ->where('wechat_bot_id', $wechatBot->id)
                ->first();
            if($wechatBotContact) {
                $wechatBotContact->restore();
            }else{
                //是否存在contact用户
                $data['type'] = WechatContact::TYPES['friend']; //1=friend
                $data['nickname'] = $data['nickname']??$data['wxid']; //默认值为null的情况
                $data['avatar'] = $data['avatar']??WechatBotContact::DEFAULT_AVATAR; //默认值为null的情况
                $data['remark'] = $data['remark']??$data['nickname']; //默认值为null的情况
                ($contact = WechatContact::firstWhere('wxid', $data['wxid']))
                    ? $contact->update($data) // 更新资料
                    : $contact = WechatContact::create($data);
                WechatBotContact::create([
                    'wechat_bot_id' => $wechatBot->id,
                    'wechat_contact_id' => $contact->id,
                    'wxid' => $contact->wxid,
                    'remark' => $data['remark']??$data['nickname'],
                    'seat_user_id' => $wechatBot->user_id, //默认坐席为bot管理员
                ]);
            }
        }

        // bot手机微信主动删除好友
        if($switchOn && $type == 'MT_CONTACT_DEL_NOTIFY_MSG'){
            WechatBotContact::query()
                ->where('wxid', $data['wxid'])
                ->where('wechat_bot_id', $wechatBot->id)
                ->first()
                ->delete();
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, '主动删除好友']);
        }


        // ✅ 收到语音消息，即刻调用转文字
        // 监控上传文件夹2 C:\Users\Administrator\AppData\Local\Temp\ =》/xbot/silk/ => /xbot/voice/
        if($type == 'MT_RECV_VOICE_MSG'){
            $msgid = $data['msgid'];
            $silk_file = $data['silk_file'];
            // "silk_file":"C:\\Users\\Administrator\\AppData\\Local\\Temp\\wxs40F9.tmp"
            // wxs40F9.tmp
            $file = str_replace('C:\\Users\\Administrator\\AppData\\Local\\Temp\\','', $silk_file);
            $xbot->toVoiceText($msgid);
            // dispach
            $content = "/storage/voices/{$wechatBot->wxid}/{$msgid}.mp3";
            SilkConvertQueue::dispatch($file, $wechatBot->wxid, $msgid);

            Log::debug(__CLASS__, [$clientId, __LINE__, $file, $content, '语音消息=》SilkConvertQueue']);
        }
        // ✅ 提取转成的文字
        // TODO 下面的post要带上 转换后的文字
        if($type == 'MT_TRANS_VOICE_MSG'){
            WechatMessageVoice::create([
                'msgid' => $data['msgid'],
                'content' => $data['text'],
            ]);
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, '语音消息转文本', $data]);
            return response()->json(null);
        }
        // ✅ 收到gif表情
        if($type == 'MT_RECV_EMOJI_MSG'){
            $content = $xml['emoji']['@attributes']['cdnurl'];
        }
        // ✅ 收到图片
            // 监控上传文件夹1 C:\Users\Public\Pictures\images =》 /xbot/images/
            // 需要手动在windows上创建 image 文件夹
            // 需要手动在windows上创建 files 文件夹 并 wx上设置 file 存储 文件夹 为  C:\Users\Public\Pictures\files
        //需要手动在云存储上 创建： /xbot/files  /xbot/images  /audios/silk =》 /audios/mp3
        if($type == 'MT_RECV_PICTURE_MSG'){
            $src_file = $data['image'];
            $msgid = $data['msgid'];
            $size = $xml['img']['@attributes']['length'];
            $dest_file = "C:\\Users\\Public\\Pictures\\images\\{$msgid}.png";
            $xbot->getImage($src_file, $dest_file, $size);
            $content = "/images/{$msgid}.png";
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $type, '收到|发送图片，已请求下载解密', $content]);
            
            WechatMessageFile::create([
                'wechat_bot_id' => $wechatBot->id,
                'msgid' => $data['msgid'],
                'path' => $dest_file, //Windows路径
                'url' => $content, //文件链接
            ]);
        }
        // ✅  文件消息
        // 监控上传文件夹3 C:\Users\Administrator\Documents\WeChat Files\  =》 /xbot/file/
        if($type == 'MT_RECV_FILE_MSG' || $type == 'MT_RECV_VIDEO_MSG'){
            $originPath = $data['file']??$data['video'];
            $file = str_replace('C:\\Users\\Public\\Pictures\\','/', $originPath);
            $content =  str_replace('\\','/', $file);
            WechatMessageFile::create([
                'wechat_bot_id' => $wechatBot->id,
                'msgid' => $data['msgid'],
                'path' => $originPath, //Windows路径
                'url' => $content, //文件链接
            ]);
            Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, $clientId, $type, '文件|视频消息', $originPath, $content]);
        }

        if($type == 'MT_RECV_TEXT_MSG'){ //接收到 个人/群 文本消息
            $content = $data['msg'];
            $replyTo = $data['from_wxid']; //消息发送者
            if($isRoom) $replyTo = $data['room_wxid'];
            if($data['from_wxid'] == $wechatBot->wxid) $replyTo = $data['to_wxid']; //自己给别人聊天时，发关键词 响应信息
            // 彩蛋:谁在线，在线时长！
            if($content=='whoami'){
                $time = $wechatBot->login_at->diffForHumans();
                $text = "已登陆 $time\n时间: {$wechatBot->login_at}\n设备ID: {$clientId}\n用户: {$wechatBot->user->name}";
                $xbot->sendText($replyTo, $text);
                // 针对文本 命令的 响应，标记 已响应，后续 关键词不再触发（return in observe）。
                // 10s内响应，后续hook如果没有处理，就丢弃，不处理了！
                // 如果其他资源 已经响应 关键词命令了，不再推送给第三方webhook了
                Cache::put('xbot.replied-'.$data['msgid'], true, 10);
            }
            // AutoReply TODO 关键词自动回复，
                // 回复模版变量消息
                // API发送模版消息
            // 响应 预留 关键词 群配置？
            // 资源：预留 关键词
                //  600 + 601～699   # LY 中文：拥抱每一天 getLy();
                //  7000 7001～7999  # Album 自建资源 Album 关键词触发 getAlbum();
                // #100  #100～#999  # LTS getLts();
        }
        if($type == 'MT_RECV_OTHER_APP_MSG') {
            if($data['wx_type'] == 49){
                $content = '音乐消息[已回复]，请到手机查看！';
                // 收到音频消息
                if(isset($data['wx_sub_type']) && $data['wx_sub_type']==3){
                    $content = "{$xml['appmsg']['title']} : {$xml['appmsg']['url']}";
                }
                //更改TYPE 执行下面的内容
                $type = 'MT_RECV_TEXT_MSG';
            }
        }
        // 把接收的消息写入 WechatMessage
        $recordWechatMessageTypes = [
            'MT_RECV_TEXT_MSG',
            'MT_RECV_VOICE_MSG',
            'MT_RECV_EMOJI_MSG',
            'MT_RECV_PICTURE_MSG',
            'MT_RECV_FILE_MSG',
            'MT_RECV_VIDEO_MSG',
            'MT_RECV_SYSTEM_MSG',
        ];
        if(in_array($type,$recordWechatMessageTypes)) {
            $fromWxid = $data['from_wxid'];
            $conversationWxid = $data['from_wxid'];
            // 被动响应的信息+主动回复给filehelper的信息

            $fromId = null;
            if($data['from_wxid'] == $wechatBot->wxid){
                // $fromId = null;
                $conversationWxid = $data['to_wxid'];
            }else{
                $from = WechatBotContact::query()
                    ->where('wechat_bot_id', $wechatBot->id)
                    ->where('wxid', $fromWxid)
                    ->first();
                if(!$from) {
                    Log::error(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid, '期待有个fromId but no from!']);
                }else{
                    $fromId = $from->id;
                }
            }
            //如果是群，别人发的信息
            if($isRoom){
                $conversationWxid = $data['room_wxid'];
            }
            $conversation = WechatBotContact::withTrashed()
                ->where('wxid', $conversationWxid)
                ->where('wechat_bot_id', $wechatBot->id)
                ->first();
            if(!$conversation) {
                Log::debug(__CLASS__, [$clientId, __LINE__, $wechatBot->wxid,  $conversationWxid, '给不是好友的人发的信息，即把他删了，对方又请求好友了，我没答应，此时还可以发信息|新群！']);
                // 下一步，搜索好友，加好友
                $xbot->addFriendBySearch($conversationWxid);
                return response()->json(null);
            }else{
                $conversation->restore();
            }
            WechatMessage::create([
                'type' => array_search($type, WechatMessage::TYPES), // 1文本
                'wechat_bot_id' => $wechatBot->id,
                'from' => $fromId, // 消息发送者:Null为bot发送的
                'conversation' => $conversation->id, //群/个人
                'content' => $content,
                'msgid' => $data['msgid'],
            ]);
        }
        // 开发者选项 =》 WechatMessageObserver
        Log::debug(__CLASS__, [$clientId, __LINE__, $type, $wechatBot->wxid, 'THE END OF CALLBACK']);//已执行到最后一行
        return response()->json(null);
    }
}
