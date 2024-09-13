<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Wechat;
use App\Http\Livewire\Webchat;
use App\Http\Livewire\WechatContent;
use App\Http\Livewire\WechatBotContact;
use App\Http\Livewire\WechatBotContactTag;
use App\Http\Livewire\WechatAutoReply;
use Illuminate\Support\Facades\Log;
use App\Jobs\reSendQueue;
use App\Models\XbotSubscription;
use App\Models\WechatBot;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::group([
    'middleware' => ['auth:sanctum', 'verified'],
    'prefix'=>'channels/wechat', 
    'as'=>'channel.wechat.',
    ], function () {
        Route::get('/', Wechat::class)->name('weixin');
        Route::get('/webchat', Webchat::class)->name('webchat');
        Route::get('/content', WechatContent::class)->name('content');
        Route::get('/contact', WechatBotContact::class)->name('contact');
        Route::get('/autoreply', WechatAutoReply::class)->name('autoreply');
        Route::get('/tags', WechatBotContactTag::class)->name('tags');
});
 // App\Models\WechatBot [281,"XbotIsLive 程序崩溃时,已下线！","wxid_7nof1pauaqyd22",10]

Route::group([
    'middleware' => ['auth:sanctum', 'verified'],
    ], function () {
        Route::get('/reSend/{keyword}', function ($keyword) {
            $botOwnerId = auth()->user()->currentTeam->owner->id;
            $wechatBot = WechatBot::where('user_id', $botOwnerId)->firstOrFail();
            $xbotSubscriptions = XbotSubscription::where('wechat_bot_id', $wechatBot->id)->where('keyword', $keyword)->get();
            
            // clear cache then reSend!
            $cacheKey = "resources.{$keyword}";
            Cache::forget($cacheKey);
            
            foreach ($xbotSubscriptions as $xbotSubscription) {
                Log::debug('reSend', [$wechatBot->id, $keyword, $xbotSubscription->id]);
                reSendQueue::dispatch($xbotSubscription);
            }
            return ['已加入发送队列','发送中，请到手机微信上查看'];
        });

        Route::get('/reSend/all', function () {
            $botOwnerId = auth()->user()->currentTeam->owner->id;
            $wechatBot = WechatBot::where('user_id', $botOwnerId)->firstOrFail();
            $xbotSubscriptions = XbotSubscription::where('wechat_bot_id', $wechatBot->id)->get();

            // clear cache then reSend!
            foreach ($xbotSubscriptions->pluck('keyword')->unique() as $keyword) {
                $cacheKey = "resources.{$keyword}";
                Cache::forget($cacheKey);
            };

            foreach ($xbotSubscriptions as $xbotSubscription) {
                $keyword = $xbotSubscription->keyword;
                Log::debug('reSendAll', [$wechatBot->id, $keyword, $xbotSubscription->id]);
                reSendQueue::dispatch($xbotSubscription);
            }
            return ['已全部加入发送队列','发送中，请到手机上查看'];
        });

});
