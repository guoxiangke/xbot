<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\WechatBot;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('xbot.{xbotId}', function ($user, $xbotId) {
    $botOwnerUid = $user->currentTeam->owner->id;
    $wechatBot = WechatBot::firstWhere('user_id', $botOwnerUid);
    if(!$wechatBot) return false;
    if((int) $wechatBot->id ===  (int) $xbotId){
        // 目的：为了节约pusher
        // @see WechatMessageObserver@created()
        // 如果有用户打开webchat页面，则30分钟内，pusher数据实时刷新
        Cache::put("xbot.{$wechatBot->id}.webchat.pusher.live", true, 1800);
        return true;
    }
    
});