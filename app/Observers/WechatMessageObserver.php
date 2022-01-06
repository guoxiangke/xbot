<?php

namespace App\Observers;

use App\Models\WechatContent;
use App\Models\WechatMessage;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookServer\WebhookCall;

class WechatMessageObserver
{
    /**
     * Handle the WechatMessage "created" event.
     *
     * @param  \App\Models\WechatMessage  $wechatMessage
     * @return void
     */
    public function created(WechatMessage $wechatMessage)
    {
        // $wechatMessage = WechatMessage::find(3);
        $wechatMessage->to; // load ::with('to')
        $wechatBot = $wechatMessage->wechatBot->load('meta');
        
        $webhookOn = $wechatBot->getMeta('webhook', true); // 已开启webhook
        $webhookUrl = $wechatBot->getMeta('webhookUrl', '/api/webhook/xbot');
        $webhookSecret = $wechatBot->getMeta('webhookSecret', 'verified-token');
        if($webhookOn && $webhookUrl && $webhookSecret){
            $data = [
                'msgid' => $wechatMessage->id,
                'type' => WechatMessage::TYPES_TEXT[$wechatMessage->type],
                'who' => $wechatMessage->to->wxid,
                'self' => $wechatMessage->isSentByBot,
                'content' => $wechatMessage->content,
            ];
            WebhookCall::create()
                ->url($webhookUrl)
                ->doNotSign()
                // ->useSecret($webhookSecret)
                ->payload($data)
                ->dispatchSync();//dispatch Now

            Log::debug(__METHOD__, ['WebhookCall', $wechatBot->wxid, $data]);
        }
    }
}
