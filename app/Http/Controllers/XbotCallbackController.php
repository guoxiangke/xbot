<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WechatBot;
use App\Models\WechatClient;
use App\Models\WechatContact;
use App\Models\WechatContent;
use App\Models\WechatBotContact;
use App\Models\WechatMessage;
use App\Models\WechatMessageFile;
use App\Models\WechatMessageVoice;
use Illuminate\Http\Request;
use App\Services\Xbot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\SilkConvertQueue;

class XbotCallbackController extends Controller
{

    public function __invoke(Request $request, $token){
        $type = $request['type']??false; // {"data":{"error":"参数错误"},"type":null,"client_id":1}
        $clientId = $request['client_id']??false;
        if(!($clientId && $type)){
            Log::error(__CLASS__, [__LINE__, $clientId, $request->all(), '参数错误']);
            return response()->json(null);
        }
        $data = $request['data'];

        $wechatClient = WechatClient::where('token', $token)->first();
        if(!$wechatClient) {
            Log::error(__CLASS__, [__LINE__, $clientId, $request->all(), '参数Token错误']);
            return response()->json(null);
        }
        $wechatClientId = $wechatClient->id;
        $wechatClientName = $wechatClient->token; //qq1windows109

        $cacheKey = $wechatClientId;
        // 1.获取到登陆二维码
        // 缓存以供前端调用扫码（2个client同一个id，如果已登陆的，不显示二维码！）
        if($type == 'MT_RECV_QRCODE_MSG') {
            $qr = [
                'qr' => $data['code'],
                'client_id' => $clientId,
            ];
            $qrPool = Cache::get("xbots.{$cacheKey}.qrPool", []);
            // 一台机器，多个客户端，使用二维码池, 池子大小==client数量，接收到1个新的，就把旧的1个弹出去
            array_unshift($qrPool, $qr);
            Cache::put("xbots.{$cacheKey}.qrPool", $qrPool);
            // 前端刷新获取二维码总是使用第一个QR，登陆成功，则弹出对于clientId的QR
            // '获取到登陆二维码，已压入qrPool',
            // TODO 发送到管理群里
            Log::debug(__CLASS__, [__LINE__, $type, $wechatClientId, $wechatClientName, $clientId, $qr]);

            //如果登陆中！
            $wechatBot = WechatBot::where('wechat_client_id', $wechatClientId)
                ->where('client_id', $clientId)
                ->first();
            if($wechatBot) $wechatBot->logout();
            return response()->json(null);
        }
        // 2.登陆成功 写入数据库
        $cliendWxid = $data['wxid']??null; //从raw-data中post过来的wxid, 部分消息没有，设为null
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
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $data['nickname'], '登陆成功','，已弹出qrPool']);

            // Or没有提前绑定
            $wechatBot = WechatBot::firstOrNew(
                ['wxid' =>  $cliendWxid],
                [
                    'user_id' => 1, //TODO 默认绑定1号假用户
                    'wechat_client_id' => $wechatClientId,
                ],
            );
            // 登陆成功，通知前端刷新页面
            $wechatBot->login($clientId);
            $data['avatar'] = str_replace('http://','https://', $data['avatar']);
            $wechatBot->setMeta('xbot', $data);

            $wechatBot->xbot()->sendText($cliendWxid, "恭喜！登陆成功，正在初始化...");
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $data['nickname'], '下面执行初始化']);
            $wechatBot->init();
            return response()->json(null);
        }

        if($type == 'MT_USER_LOGOUT'){
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $cliendWxid, 'MT_USER_LOGOUT']);
            $wechatBot = WechatBot::where('wxid', $cliendWxid)->first();
            $wechatBot->logout();
            return response()->json(null);
        }
        // {"type":"MT_CLIENT_DISCONTECTED","client_id":4}
        if($type == 'MT_CLIENT_DISCONTECTED'){
            Log::info(__CLASS__, [__LINE__, $wechatClientName, '主动退出windows微信客户端']);
            $wechatBot = WechatBot::where('wechat_client_id', $wechatClientId)
                ->where('client_id', $clientId)
                ->first();
            if($wechatBot){
                $wechatBot->logout();
            }else{
                Log::info(__CLASS__, [__LINE__, $wechatClientName, '主动退出还未登陆的windows微信客户端']);
            }
            return response()->json(null);
        }

        // MT_DATA_OWNER_MSG
        if($type == 'MT_DATA_OWNER_MSG') {
            $wechatBot = WechatBot::where('wxid', $cliendWxid)->first();
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
        if(isset($data['timestamp']) && $data['timestamp']>0 &&  now()->timestamp - $data['timestamp'] > 1*60*60 ) {
            Log::debug(__CLASS__, [__LINE__, $wechatClientName,now()->timestamp, $data['timestamp'], '忽略1小时以上的信息']);
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
            "MT_TALKER_CHANGE_MSG" => '客户端点击头像',
            "MT_RECV_REVOKE_MSG" => 'xx 撤回了一条消息',
            "MT_DECRYPT_IMG_MSG_TIMEOUT" => '图片解密超时',
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
            // 'MT_DECRYPT_IMG_MSG_TIMEOUT',
            'MT_DATA_OWNER_MSG', // 获取到bot信息
            'MT_RECV_VIDEO_MSG',
            'MT_ROOM_CREATE_NOTIFY_MSG',
            'MT_CLIENT_CONTECTED', // 新增加一个客户端，调用获取QR，以供web登陆
            // {"type":"MT_CLIENT_DISCONTECTED","client_id":4}
            'MT_RECV_REVOKE_MSG', //默认开启 消息防撤回！不再处理这个
            'MT_DATA_CHATROOM_MEMBERS_MSG',
        ];
        if(!in_array($type, $ignoreRAW)){
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $type, $request->all()]);
        }
        //**********************DEBUG IGNORE END***********************************
        // 新增加一个客户端，主动调用获取QR，压入缓存，以供web登陆
        // {"type":"MT_CLIENT_CONTECTED","client_id":8}
        if($type == 'MT_CLIENT_CONTECTED'){
            $xbot = new Xbot($wechatClient->xbot, $botWxid='null', $clientId);
            $respose = $xbot->loadQR();
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $type, '新增加一个客户端，主动调用获取QR']);
            return response()->json(null);
        }
        // 主动关闭 一个clientId
        // {"type":"MT_CLIENT_DISCONTECTED","client_id":4}
        //*********************************************************
        // 通过clientId 找到对应的wechatBot
        // 群消息中，没有Bot的wxid  "from_wxid":"xxx"  "to_wxid":"23887@chatroom"
        $wechatBot = WechatBot::where('wechat_client_id', $wechatClientId)
            ->where('client_id', $clientId)
            ->first();
        if(!$wechatBot) {
            Log::error(__CLASS__, [__LINE__, $wechatClientName, $wechatClientId, $clientId, ' 不存在wechatBot？设备已下线！']);
            return response()->json(null);
        }
        //*********************************************************
        $botWxid = $data['to_wxid']??null;

        $content = ''; //写入 WechatMessage 的 content
        $config = $wechatBot->getMeta('xbot.config', [
            'isAutoWcpay' => false, // MT_RECV_WCPAY_MSG
            'isAutoAgree' => false, // 自动同意好友请求
            'isWelcome' => false,
            'weclomeMsg' => 'hi',
            'isListenRoom' => false,
            'isListenRoomAll' => false,
            'isAutoReply' => false, // 关键词自动回复
        ]);

        // AutoReply  响应 预留 关键词 + 群配置
        $islistenMsg = true; //默认是记录消息，但是在群里，需要判断
        $isAutoReply = $config['isAutoReply']??false;



        $isSelf = false;
        $from_wxid = $data['from_wxid']??'';
        $to_wxid = $data['to_wxid']??'';
        if($from_wxid == $to_wxid || $from_wxid == $wechatBot->wxid){
            $isSelf = true;
            //自己发给自己消息，即不发送给develope
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, "isSelf={$isSelf}"]);
            //因bot发的信息（通过关键词响应的信息）也要记录，所以继续走下去吧！不return了！
            // return response()->json(null);
        }

        $isRoom = $data['room_wxid']??false; //群
        if($isRoom){
            $isListenRooms = $wechatBot->getMeta('isListenRooms', []);
            $isReplyRooms = $wechatBot->getMeta('isReplyRooms', []);

            $replyTo = $data['room_wxid'];
            $isAutoReply = $isReplyRooms[$replyTo]??false; // 选择某些群来响应关键词
            if(!$config['isListenRoomAll']) //如果不是监听所有群消息，则从配置中取
                $islistenMsg = $isListenRooms[$replyTo]??false; // 选择某些群来记录消息

            // Log::error(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid,  $isSelf, '自己响应的群消息，只记录，不响应autoprely']);
            if(!$isSelf){
                // 接收到群消息！群消息里，没有wxid, from_wxid = 发送者，to_wxid=wx@room room_wxid=wx@room
                Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, '接收到群消息']);
                // 是否记录群消息: isListenRoom
                // 是否记录所有的群消息: isListenRoomAll
                if(!$config['isListenRoom']){
                    Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, '!终止执行,没有监听群消息']);
                    $islistenMsg = false;
                    // 有没有可能不记录，但是响应 关键词 回复？？
                    // return response()->json(null);
                }
                // //  && $islistenMsg
                // if(!($config['isListenRoomAll'] || $islistenMsg)){
                //     Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, '!终止执行，没有开启监听所有/本群消息']);
                //     $islistenMsg = false;
                //     // 有没有可能不记录，但是响应 关键词 回复？？
                //     // return response()->json(null);
                // }
            }
        }

        // 初始化 联系人数据
        $xbotContactCallbackTypes = ['MT_DATA_FRIENDS_MSG', 'MT_DATA_CHATROOMS_MSG', 'MT_DATA_PUBLICS_MSG' ];
        if(in_array($type, $xbotContactCallbackTypes)){
            $wechatBot->syncContacts($data, $type);
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, '获取联系人', $type]);
            return response()->json(null);
        }
        // MT_ROOM_ADD_MEMBER_NOTIFY_MSG 新人入群
        // MT_ROOM_CREATE_NOTIFY_MSG 被拉入群
        // MT_DATA_CHATROOM_MEMBERS_MSG 主动获取 群成员信息，入库 不需要了，只有wxid，没有其他信息，使用再次getRooms()再次入库
        if($type == 'MT_ROOM_ADD_MEMBER_NOTIFY_MSG' || $type == 'MT_ROOM_CREATE_NOTIFY_MSG'){
            // 创建群后，再次手动掉getRooms()以执行273行 来初始化群数据
            $wechatBot->xbot()->getRooms();
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
                    if(!$gBotContact){
                        Log::error(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $member['wxid'], '！bot被出群了！消息删除了？']);
                        continue;
                    }
                    WechatMessage::query()
                        ->where('from', $gBotContact->id)
                        ->delete();
                    Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $gBotContact->nickname, $gBotContact->id, '群成员变动，删除消息']);
                    $gBotContact->delete();
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
                Log::error(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $gBotContact->nickname, $gBotContact->id, 'bot被出群了！消息删除了']);
            }
        }


        //??? 说明是被动响应的信息，丢弃，不然自己给自己聊天了！
        // if(!$wechatBot) {
        //     Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, '被动响应的信息', '已丢弃']);
        //     return response()->json(null);
        // }
        if(!($wechatBot || $botWxid)){
            Log::error(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $request->all()]);
        }

        //************************************************
        $xbot = $wechatBot->xbot($clientId);
        //************************************************
        if(isset($data['raw_msg'])){
            $tmpData = $data['raw_msg'];
            if(Str::startsWith($tmpData, '<?xml ') || Str::startsWith($tmpData, '<msg')) {
                 $xml = xStringToArray($tmpData);
            }else{
                Log::error(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, 'raw data not xml']);
                // MT_RECV_SYSTEM_MSG "raw_msg":"你已添加了天空蔚蓝，现在可以开始聊天了。"
                $data['msg'] = $data['raw_msg'];
                $content = $data['msg'];
            }
        }
        if(isset($data['to_wxid']) && $data['to_wxid'] == "filehelper") {
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, '自己发给自己的filehelper消息，暂不处理！']);
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
            if(isset($data['v1']) && isset($data['v2'])){
                $remark = "朋友介绍"; //todo remark settings in FE
                $xbot->addFriendBySearchCallback($data['v1'], $data['v2'], $remark);
                Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, '主动+好友', $data['search']]);
            }else{
                $xbot->getRooms(); //更新群
                Log::error(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, '更新群成员入库', $data]);
            }
            return response()->json(null);
        }

        // ✅ 收到好友请求
        $switchOn = $config['isAutoAgree'];
        if($switchOn && $type == 'MT_RECV_FRIEND_MSG'){
            $attributes = $xml['@attributes'];
            // $scene = 3: 14: 从群里添加 6:拉黑用户再次请求;
            $xbot->agreenFriend($attributes['scene'], $attributes['encryptusername'], $attributes['ticket']);
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, "收到{$attributes['fromnickname']}的好友请求:{$attributes['content']}"]);
            return response()->json(null);
        }

        // ✅ 手动同意好友请求 发送 欢迎信息
        $switchOn = $config['isWelcome'];
        if($switchOn && $type == 'MT_CONTACT_ADD_NOITFY_MSG'){
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, '同意好友请求 发送 欢迎信息']);
            $xbot->sendText($cliendWxid, $config['weclomeMsg']);
            // 写入数据库
            $wechatBotContact = WechatBotContact::query()
                ->withTrashed()
                ->where('wxid', $cliendWxid)
                ->where('wechat_bot_id', $wechatBot->id)
                ->first();
            if($wechatBotContact) {
                $wechatBotContact->restore();
            }else{
                //是否存在contact用户
                $data['type'] = WechatContact::TYPES['friend']; //1=friend
                $data['nickname'] = $data['nickname']??$cliendWxid; //默认值为null的情况
                $data['avatar'] = $data['avatar']??WechatBotContact::DEFAULT_AVATAR; //默认值为null的情况
                $data['remark'] = $data['remark']??$data['nickname']; //默认值为null的情况
                ($contact = WechatContact::firstWhere('wxid', $cliendWxid))
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
                ->where('wxid', $cliendWxid)
                ->where('wechat_bot_id', $wechatBot->id)
                ->first()
                ->delete();
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, '主动删除好友']);
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
            $date = date("ym");
            $content = "/storage/voices/{$date}/{$wechatBot->wxid}/{$msgid}.mp3";
            $silkDomain = $wechatClient->silk;
            SilkConvertQueue::dispatch($file, $wechatBot->wxid, $msgid, $silkDomain, $date);

            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $file, $content, '语音消息=》SilkConvertQueue']);
        }
        // ✅ 提取转成的文字
        // TODO 下面的post要带上 转换后的文字
        if($type == 'MT_TRANS_VOICE_MSG'){
            WechatMessageVoice::create([
                'msgid' => $data['msgid'],
                'content' => $data['text'],
            ]);
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, '语音消息转文本', $data]);
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
            $date = date("ym");
            $src_file = $data['image'];
            $msgid = $data['msgid'];
            $size = $xml['img']['@attributes']['hdlength']??$xml['img']['@attributes']['length'];
            $md5 = $xml['img']['@attributes']['md5']??$msgid;
            $dest_file = "C:\\Users\\Public\\Pictures\\images\\{$date}\\{$md5}.png";
            // if file_exist($md5), 则不再下载！
            $xbot->decryptImage($src_file, $dest_file, $size);
            $content = "/images/{$date}/{$md5}.png";
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $type, '收到|发送图片', $src_file, $dest_file, $size, $content]);

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
            Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, $clientId, $type, '文件|视频消息', $originPath, $content]);
        }

        if($type == 'MT_RECV_TEXT_MSG'){ //接收到 个人/群 文本消息
            $content = $data['msg'];
            $replyTo = $data['from_wxid']; //消息发送者
            if($isRoom) $replyTo = $data['room_wxid'];
            if($data['from_wxid'] == $wechatBot->wxid) $replyTo = $data['to_wxid']; //自己给别人聊天时，发关键词 响应信息
            // 彩蛋:谁在线，在线时长！
            if($content=='whoami'){
                $time = optional($wechatBot->login_at)->diffForHumans();
                $text = "已登陆 $time\n时间: {$wechatBot->login_at}\n设备: {$clientId}号端口@Windows{$wechatBot->wechat_client_id}\n用户: {$wechatBot->user->name}";
                $xbot->sendText($replyTo, $text);
                // 针对文本 命令的 响应，标记 已响应，后续 关键词不再触发（return in observe）。
                // 10s内响应，后续hook如果没有处理，就丢弃，不处理了！
                // 如果其他资源 已经响应 关键词命令了，不再推送给第三方webhook了
                Cache::put('xbot.replied-'.$data['msgid'], true, 10);
            }
            if($isAutoReply) {
                $keywords = $wechatBot->autoReplies()->pluck('keyword','wechat_content_id');
                foreach ($keywords as $wechatContentId => $keyword) {
                    // TODO preg; @see https://laravel.com/docs/8.x/helpers#method-str-is
                    if(Str::is($keyword, $content)){
                        Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid, '关键词回复', $keyword]);
                        $wechatBot->send([$replyTo], WechatContent::find($wechatContentId));
                    }
                }
            }
            // 资源：预留 关键词
                //  600 + 601～699   # LY 中文：拥抱每一天 getLy();
                //  7000 7001～7999  # Album 自建资源 Album 关键词触发 getAlbum();
                // #100  #100～#999  # LTS getLts();
        }
        if($type == 'MT_RECV_OTHER_APP_MSG') {
            if($data['wx_type'] == 49){
                $content = '其他消息，请到手机查看！';
                // 收到音频消息
                if(isset($data['wx_sub_type'])){
                    switch ($data['wx_sub_type']) {
                        case  3:
                            $title = $xml['appmsg']['title']??'';
                            $content = "音乐消息｜{$title}: {$xml['appmsg']['url']}";
                            break;
                        case  19: //聊天记录
                            $content = "{$xml['appmsg']['title']} : {$xml['appmsg']['des']}";
                            break;
                        case  36: //百度网盘
                            $content = "{$xml['appmsg']['sourcedisplayname']} ｜ {$xml['appmsg']['title']} : {$xml['appmsg']['des']} : {$xml['appmsg']['url']} ";
                            break;
                        case  51:
                            $content = "视频号｜{$xml['appmsg']['finderFeed']['nickname']} : {$xml['appmsg']['finderFeed']['desc']}";
                            break;
                        case  57:
                            $content = "引用回复｜{$xml['appmsg']['title']}";
                            break;
                        default:
                            $content = "其他未处理消息，请到手机查看！";
                            $content .= $xml['appmsg']['title']??'';
                            $content .= $xml['appmsg']['des']??'';
                            $content .= $xml['appmsg']['desc']??'';
                            $content .= $xml['appmsg']['url']??'';
                            break;
                    }
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
        if($islistenMsg && in_array($type,$recordWechatMessageTypes)) {
            $fromWxid = $data['from_wxid'];
            $conversationWxid = $data['from_wxid'];
            // 被动响应的信息+主动回复给filehelper的信息

            $fromId = null;
            if($data['from_wxid'] == $wechatBot->wxid){
                // $fromId = null;
                $conversationWxid = $data['to_wxid'];
            }else{
                if($isSelf) {
                    $fromId = null;
                }else{
                    $from = WechatBotContact::query()
                        ->where('wechat_bot_id', $wechatBot->id)
                        ->where('wxid', $fromWxid)
                        ->first();
                    if(!$from) {
                        if($isRoom){
                            // 接口初始化一下(本群的)所有群的所有群成员
                            // 收到执行，修复bug, 300行已解决
                            return $xbot->getRooms();
                        }else{
                            Log::error(__CLASS__, [__LINE__, $wechatBot->id, $fromWxid, $wechatClientName, $wechatBot->wxid, '期待有个fromId but no from!',$request->all()]);
                        }
                    }else{
                        $fromId = $from->id;
                    }
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
                Log::debug(__CLASS__, [__LINE__, $wechatClientName, $wechatBot->wxid,  $conversationWxid, '给不是好友的人发的信息，即把他删了，对方又请求好友了，我没答应，此时还可以发信息|新群！']);
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
            $wechatBot->replyResouceByKeyword($content);
        }
        Log::debug(__CLASS__, [__LINE__, $wechatClientName, $type, $wechatBot->wxid, '******************']);//已执行到最后一行
        return response()->json(null);
    }
}
