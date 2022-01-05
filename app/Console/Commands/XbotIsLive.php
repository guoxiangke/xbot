<?php

namespace App\Console\Commands;

use App\Models\WechatBot;
use App\Models\WechatContent;
use App\Services\Wechat;
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
        WechatBot::query()
            ->whereNotNull('client_id')
            ->whereNotNull('login_at')
            ->each(function(WechatBot $wechatBot){
                // 主动调用一个 MT_DATA_OWNER_MSG 接口
                // 只有这里主动调用这个 接口
                $time = $wechatBot->login_at->diffForHumans();
                $content = "登陆时间: {$wechatBot->login_at} $time\n设备ID: {$wechatBot->clientId}\n用户: {$wechatBot->user->name}";

               $wechatContent =  WechatContent::make([
                    'name' => 'tmpSendStructure',
                    'type' => 0, //text=>0
                    'content' => compact('content'),
                ]);
                $wechatBot->send(['filehelper'], $wechatContent);
            });
    }
}
