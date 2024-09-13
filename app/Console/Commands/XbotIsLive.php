<?php

namespace App\Console\Commands;

use App\Models\WechatBot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class XbotIsLive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xbot:islive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check xbot is live or not by filehelper';

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
        // 程序崩溃时，login_at 还在，咋办？ 
        // 每5分钟执行一次 MT_DATA_OWNER_MSG
        // if ($wechatBot->is_live_at->diffInMinutes() > 5) 则代表离线，清空login_at
        WechatBot::query()
            ->whereNotNull('client_id')
            ->whereNotNull('is_live_at')
            ->each(function(WechatBot $wechatBot){
                $isLive = $wechatBot->isLive();
                if(!$isLive) {
                    Log::error('XbotIsNotLive', [$wechatBot->name, $wechatBot->wxid, $isLive, __CLASS__]);
                    //TODO send alert message by sms/email/wechat!
                    $wechatBot2 = WechatBot::find(7);
                    $wechatBot2->xbot()->sendText("17916158456@chatroom", "whoami");//Febc微信机器人掉线监控
                    $wechatBot2->xbot()->sendText("5829025039@chatroom", "whoami");//"主人0421"

                    $content = "掉线了:".$wechatBot->name;
                    $wechatBot2->xbot()->sendText("5829025039@chatroom", $content);

                    $url = "https://api.day.app/hzJ44um4NTx9JWoNJ5TFia/$content";
                    file_get_contents($url);
                }
            });
    }
}
