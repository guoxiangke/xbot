<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WechatClient;
use App\Models\WechatBotContact;
use App\Models\WechatContact;
use App\Models\WechatBot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class checkFriend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'friend:check {wechatBotId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '好友检测';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // - 拉群/非好友检测
        //  - 加成员进<40 3人小群，如果5s没有入群消息： "raw_msg":"你邀请\"AI机器人\"加入了群聊" ，则说明 拉黑了，将其备注A00-xxx
        //  - 如果入群成功，然后将其移出群: 你将\"AI机器人\"移出了群聊 ，下一位！

        $wechatBotId = $this->argument('wechatBotId');
        $wechatBot = \App\Models\WechatBot::find($wechatBotId);
        $xbot = $wechatBot->xbot();
        // 由于异步，即使你创建了一个群，也无法获得群名，需要用户手动创建一个群，并更名为 好友检测！
        $wechatContact = $wechatBot->contacts(WechatContact::TYPE_GROUP)->where('nickname', '好友检测')->get()->last();
        $wechatBotContact = WechatBotContact::find($wechatContact->pivot->id); //room
        $roomMeta = $wechatBotContact->getMeta('group');
        $count = 0;
        $cacheKey = 'check-friend-'.$wechatBot->wxid;
        Cache::forget($cacheKey);
        foreach ($wechatBot->wechatBotContacts(WechatContact::TYPE_FRIEND)->get() as $contact) {
            if(in_array($contact->wxid, $roomMeta['member_list']))  continue; // 已经在群里的好友不用检测！
            $xbot->addMememberToRoom($wechatBotContact->wxid, $contact->wxid);
            sleep(10);
            $cache = Cache::get($cacheKey, false);
            if($cache){ //是好友
                $xbot->deleteRoomMemember($wechatBotContact->wxid, $contact->wxid);
            }else{//不是好友
                Log::debug(__LINE__,[++$count, $cache, '不是好友', $contact->toArray()]);
                $xbot->sendText($contact->wxid, "检测出!好友");
            }
            Cache::forget($cacheKey);
        }

        return 0;
    }
}
