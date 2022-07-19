<?php

namespace App\Observers;

use App\Models\WechatBotContact;

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
