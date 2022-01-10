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
        // 程序崩溃时，login_at 还在，咋办？ 
        // 每5分钟执行一次 MT_DATA_OWNER_MSG
        // if ($wechatBot->is_live_at->diffInMinutes() > 5) 则代表离线，清空login_at
        WechatBot::query()
            ->whereNotNull('client_id')
            ->whereNotNull('login_at')
            ->whereNotNull('is_live_at')
            ->each(function(WechatBot $wechatBot){
                $wechatBot->isLive();
            });
    }
}
