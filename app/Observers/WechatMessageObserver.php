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
        // TODO 1s内来100条信息，只刷新一次
        $wechatBotId = $wechatMessage->wechat_bot_id;
        $countsKey = "unread.{$wechatBotId}.counts";
        $counts = Cache::store('file')->increment($countsKey); //来了多少条消息

        if(Cache::get("xbot.{$wechatBotId}.webchat.pusher.live", false)){
            $lastTimesampKey = "unread.{$wechatBotId}.lastTimestamp";
            $lastTimesamp = Cache::store('file')->get($lastTimesampKey, now());
            $currentTimesamp = $wechatMessage->created_at;
            $diff = $currentTimesamp->diffInSeconds($lastTimesamp);
            if($diff>=3 || $counts>100){
                WechatMessageCreated::dispatch($wechatMessage);
                Cache::store('file')->set($lastTimesampKey, now());
                Log::error(__CLASS__, [__LINE__, $wechatBotId, 'pusher', $counts,"条  diff= {$diff}"]);
                Cache::store('file')->forget($countsKey); // 已实时推送
            }else{
                Log::error(__CLASS__, [__LINE__, $wechatBotId, '!pusher', $counts,"条  diff= {$diff}"]);
            }
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
                'id' => $wechatMessage->id,
                'msgid' => $wechatMessage->msgid,
                'type' => WechatMessage::TYPES_TEXT[$wechatMessage->type],
                'wxid' => $wechatMessage->to->wxid,
                'remark' => $wechatMessage->to->remark,
                'avatar' => $wechatMessage->to->contact->avatar,
                'seat_user_id' => $wechatMessage->to->seat_user_id,
                'self' => $wechatMessage->isSentByBot,
                'content' => $wechatMessage->content,
            ];
            // 群消息
            if($wechatMessage->from){
                $data['from'] = $wechatMessage->by->wxid;
                $data['from_remark'] = $wechatMessage->by->remark;
            }
            WebhookCall::create()
                ->url($webhookUrl)
                // ->doNotSign()
                ->useSecret($webhookSecret)
                ->payload($data)
                ->dispatchSync();//dispatch Now

            if(isset($config['webhookUrl2']) && $config['webhookUrl2']){
                Log::debug(__METHOD__, ['webhookUrl2', $wechatBot->wxid, $data]);
                WebhookCall::create()
                    ->url($config['webhookUrl2'])
                    ->doNotSign()
                    ->payload($data)
                    ->dispatchSync();//dispatch Now
            }

            Log::debug(__METHOD__, [__LINE__, $wechatBot->wxid, $data]);
        }
    }
}
