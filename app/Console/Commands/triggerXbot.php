<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\XbotSubscription;

class triggerXbot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:xbot {subscription}';

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
        $subscriptionId = $this->argument('subscription');
        $xbotSubscription = XbotSubscription::find($subscriptionId);

        $to = $xbotSubscription->wechatBotContact->wxid;
        $keyword = $xbotSubscription->keyword;

        // $wechatBot = $xbotSubscription->wechatBotContact->wechatBot;
        $wechatBot = $xbotSubscription->wechatBot;
        // $xbot = $wechatBot->xbot();
        // 同时发送多个关键词
        $keywords = explode(';', $keyword);
        foreach ($keywords as $keyword) {
            $res = $wechatBot->getResouce($keyword);
            if(!$res) {
                $autoReply = $wechatBot->autoReplies()->where('keyword', $keyword)->first();
                if($autoReply){
                    $res = $autoReply->content;//$wechatContent
                }else{
                   $res=[
                        'type'=>'text', 
                        'data'=>['content'=>$keyword]
                    ];
                }
            }
            $wechatBot->send([$to], $res);
        }

        return 0;
    }
}
