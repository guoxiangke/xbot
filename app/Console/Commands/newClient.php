<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WechatClient;

class newClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new:client {clientId}';

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
        $clientId = $this->argument('clientId');
        $wechatClient = WechatClient::find($clientId);
        $wechatClient->new();
        return 0;
    }
}
