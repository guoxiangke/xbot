<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\XbotCallbackController;
use App\Http\Controllers\WechatBotController;
use Illuminate\Support\Facades\Log;
use App\Models\WechatBot;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/xbot/callback/{token}', XbotCallbackController::class);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/wechat/send', [WechatBotController::class, 'send']);
    Route::post('/wechat/add', [WechatBotController::class, 'add']);
    Route::get('/wechat/friends', [WechatBotController::class, 'getFriends']);
});


// '26' = [// lu // '7' = [// xiaoyong
// Inbox webhook of chatwoot
Route::post('/chatwoot/{wechatBotid}', function (Request $request, $wechatBotid) {
    $messageType = $request['message_type']; //只处理 outgoing ，即发送的消息，=》xbot处理发送。ignore incoming
    $event = $request['event']; //只处理message_created，不处理conversation_updated
    if($event == 'message_created' && $messageType == 'outgoing'){
        $content = $request['content'];
        $to_wxid = $request['conversation']['meta']['sender']['identifier'];
        $wechatBot = WechatBot::find($wechatBotid);
        $wechatBot->xbot()->sendText($to_wxid, $content);//TODO add to send queue!
        Log::debug('App\Api\Webhook\Chatwoot', [$to_wxid, $content, $request['conversation']['meta']['sender']['name']]);
    }
    return true;
});

Route::post('/xbot/chatwoot', function (Request $request) {
    Log::debug('WebHook_chatwoot',$request->all());

    $message_type = $request['message_type']; //incoming
    if($message_type != 'incoming') return []; //只处理contact发的信息

    $event = $request['event'];
    if($event == 'message_created'){
        $content_type = $request['content_type']; //text

        $user = $request['account']['name']; //以琳科技
        $inbox = $request['inbox']['name']; //X牧

        if($content_type == 'text'){
            $content = $request['content']; //hi22222

            $contact = $request['conversation']['meta']['sender']['id']."\n";
            $contact .= $request['conversation']['meta']['sender']['name']."\n";
            $contact .= $request['conversation']['meta']['sender']['email']."\n";
            $contact .= $request['conversation']['meta']['sender']['phone_number']."\n";
            // Undefined array key "country_code"
            // $contact .= $request['conversation']['meta']['sender']['additional_attributes']['country_code']."\n";
            $contact .= $request['conversation']['meta']['sender']['additional_attributes']['created_at_ip']??'no-ip';

            $wechatBot= WechatBot::find(7);
            $content = "Message received:\n=============\n".$content."\n=============\nBy:".$contact;
            if(Str::of($request['conversation']['meta']['sender']['email'])->endsWith(['@chatroom', '@wx.com'])) return; //微信里的消息，不再次hook推送
            $wechatBot->xbot()->sendText('bluesky_still', $content);
        }

    }
    return [];
});


// webhook client test!
// TODO：
    // https://github.com/spatie/laravel-webhook-client
    // webhook client need verified with aravel-webhook-client

// hack xbot
Route::post('/xbot/login', function (Request $request) {
    Log::debug('LOGIN',[$request->all()]);
    return [
        "err_code" => 0,
        "license" => config('xbot.license'),
        "version" => "1.0.7",
        "expired_in"=> 2499184
    ];
});
Route::post('/xbot/heartbeat', function (Request $request) {
    return [
        "err_code"=> 0,
        "expired_in"=> 2499184
    ];
});
Route::post('/xbot/license/info', function (Request $request) {
    return [
        "err_code"=> 0,
        "license" => config('xbot.license'),
    ];
});
