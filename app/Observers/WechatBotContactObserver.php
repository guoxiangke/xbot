<?php

namespace App\Observers;

use App\Models\WechatContent;
use App\Models\WechatMessage;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookServer\WebhookCall;
use Illuminate\Support\Facades\Cache;
use App\Events\WechatMessageCreated;

class WechatBotContactObserver
{
    // 同步更新remark到手机微信！
    public function updated(WechatBotContact $wechatBotContact)
    {
        if($wechatBotContact->wasChanged('remark')) {
            $wechatBotContact->wechatBot->xbot()->remark($wechatBotContact->wxid, $wechatBotContact->remark);
        }
    }
}
