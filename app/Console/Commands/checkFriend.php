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
        $wechatBot = \App\Models\WechatBot::find($this->argument('wechatBotId'));
        foreach ($wechatBot->wechatBotContacts(WechatContact::TYPE_FRIEND)->get() as $contact) {
            $wechatBot->xbot()->checkFriendShip($contact->wxid);
        }

        return 0;
    }
}
