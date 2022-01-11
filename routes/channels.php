<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\WechatBot;

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
    return  (int) $wechatBot->id ===  (int) $xbotId;
});