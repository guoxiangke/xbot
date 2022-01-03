<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\XbotController;
use App\Http\Controllers\WechatBotController;

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

// CALLBACK
// address 传当前windows机器的ip，一般是内网ip+端口，但是要暴露出来
// api/xbot/callback/ip%3Aport
    // https://winscp.net/eng/docs/session_url
    // $address = base64_encode('127.0.0.1:123');
// => API token : YtBKZrQe4hOYkrTkjVVHJS03p4cMmsknVukL5TwF
Route::post('/xbot/callback/{token}', [XbotController::class, 'callback']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/wechat/send', [WechatBotController::class, 'send']);
    Route::post('/wechat/add', [WechatBotController::class, 'add']);
});