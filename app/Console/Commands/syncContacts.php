<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WechatBotContact;
use App\Models\WechatBot;
use App\Chatwoot\Chatwoot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class syncContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatwoot:init-contacts {wechatBotId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $wechatBotId = $this->argument('wechatBotId');
        // 0公众号，1联系人，2群, 3群陌生人
        $chatwoot = new Chatwoot(WechatBot::find($wechatBotId));
        // WechatBotContact::with('contact')->where('wechat_bot_id', 7)->where('type', 1)->get()->count();
        WechatBotContact::with('contact')
            ->where('wechat_bot_id', $wechatBotId)
            ->where('type', [1,2,3]) // 好友、群聊、群友
            ->chunk(100, function (Collection $wechatBotContacts) use($chatwoot) {
                foreach ($wechatBotContacts as $wechatBotContact) {
                    $contact = $chatwoot->saveContact($wechatBotContact);
                    // $label="群聊"
                    $label = $wechatBotContact::TYPES_NAME[$wechatBotContact->type];
                    $chatwoot->setLabelByContact($contact, $label);
                    Log::debug(__CLASS__, [$wechatBotContact->wxid]);
                    if(isset($contact['thumbnail']) && !$contact['thumbnail']){
                        $chatwoot->updateContactAvatar($contact);
                    }
                }
            });
    }
}
