<?php

namespace App\Observers;

use App\Models\WechatBotContact;
use App\Chatwoot\Chatwoot;
use Illuminate\Support\Facades\Log;

class WechatBotContactObserver
{
    // 同步更新remark到手机微信！
    public function updated(WechatBotContact $wechatBotContact)
    {
        if($wechatBotContact->wasChanged('remark')) {
            $wechatBotContact->wechatBot->xbot()->remark($wechatBotContact->wxid, $wechatBotContact->remark);
        }
    }

    // Only Luke 同步到 chatwoot 
    public function created(WechatBotContact $wechatBotContact)
    {
        $wechatBotId = $wechatBotContact->wechat_bot_id;
        if($wechatBotId != 13) return;
        $chatwoot = new Chatwoot(WechatBot::find($wechatBotId));
        $contact = $chatwoot->saveContact($wechatBotContact);
        // $label="好友"
        $label = $wechatBotContact::TYPES_NAME[$wechatBotContact->type];
        $chatwoot->setLabelByContact($contact, $label);
        Log::debug(__CLASS__, [__LINE__, $wechatBotContact, $contact]);
    }
}
