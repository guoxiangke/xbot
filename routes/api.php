<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\XbotCallbackController;
use App\Http\Controllers\WechatBotController;
use Illuminate\Support\Facades\Log;

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
