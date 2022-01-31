<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WechatBot;
use App\Models\WechatContent;

class triggerXbot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:xbot {botId} {to} {keyword}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For 定时cron发送 关键词到群/个人，触发其他bot发送资源消息';

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
        $botId = $this->argument('botId');
        $to = $this->argument('to');
        $keyword = $this->argument('keyword');

        $wechatBot = WechatBot::find($botId);
        $xbot = $wechatBot->xbot();
        $resource = app("App\Services\Resource");
        $res = $resource->__invoke($keyword);
        $wechatContent =  WechatContent::make([
            'name' => 'tmpSendStructure',
            'type' => array_search($res['type'], WechatContent::TYPES), //text=>0 这里使用0～9方便数据库存储数字
            'content' => $res['data'],
        ]);
        $wechatBot->send([$to], $wechatContent);

        return 0;
    }
}
