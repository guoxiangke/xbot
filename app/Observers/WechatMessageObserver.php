<?php

namespace App\Observers;

use App\Models\WechatContent;
use App\Models\WechatMessage;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookServer\WebhookCall;
use Illuminate\Support\Facades\Cache;
use App\Events\WechatMessageCreated;

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
        // 前端1h内实时刷新
        // 目的：为了节约pusher
        // @see Broadcast::channel('xbot.{xbotId}' ..
        // 如果有用户打开webchat页面，则1h内，pusher数据实时刷新
        // @see class WechatMessageCreated implements ShouldBroadcast
        if(Cache::get("xbot.{$wechatMessage->wechat_bot_id}.webchat.pusher.live", false)){
            WechatMessageCreated::dispatch($wechatMessage);
        }

        // 如果是bot响应的消息，不再转发
        if(is_null($wechatMessage->from)) return;
        // 如果是坐席发送的信息？

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

            Log::debug(__METHOD__, [__LINE__, $wechatBot->wxid, $data]);
        }
    }
}
