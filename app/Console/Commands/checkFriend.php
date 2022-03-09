<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WechatBot;

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
        $wechatBot = WechatBot::find($this->argument('wechatBotId'));
        foreach ($wechatBot->wechatBotContacts(WechatContact::TYPE_FRIEND)->get() as $contact) {
            $wechatBot->xbot()->checkFriendShip($contact->wxid);
        }

        return 0;
    }
}
