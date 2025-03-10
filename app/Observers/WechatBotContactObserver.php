<?php

namespace App\Observers;

use App\Models\WechatBotContact;
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
    // Not work，因为 首次添加好友时，微信提供的信息不全，只有一个 wxid
    // @see WechatBot->syncContacts()
}
