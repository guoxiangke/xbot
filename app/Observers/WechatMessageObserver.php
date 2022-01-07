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
        // 如果其他资源 已经响应 关键词命令了，不再推送给第三方webhook了
        $isReplied = Cache::get('xbot.replied-'.$wechatMessage->msgid, false);
        if($isReplied) return;

        $wechatMessage->to; // load ::with('to')
        $wechatBot = $wechatMessage->wechatBot->load('meta');
        
        //TODO 只转发 部分群
        $config = $wechatBot->getMeta('xbot.config', [
            'isListenRoom' => false,
            'isListenRoomAll' => false,
            'isWebhook' => false,
            'webhookUrl' => '',
            'webhookSecret' => '',
        ]);

        $webhookOn = $config['isWebhook'];
        $webhookUrl = $config['webhookUrl'];
        $webhookSecret = $config['webhookSecret'];

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
                // ->doNotSign()
                ->useSecret($webhookSecret)
                ->payload($data)
                ->dispatchSync();//dispatch Now

            Log::debug(__METHOD__, ['WebhookCall', $wechatBot->wxid, $data]);
        }
    }
}
