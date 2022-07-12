<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWechatBotRequest;
use App\Http\Requests\UpdateWechatBotRequest;
use App\Models\WechatContent;
use App\Models\WechatBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WechatBotController extends Controller
{

    // {"type":"text", "to":"bluesky_still", "data": {"content": "API主动发送 文本/链接/名片/图片/视频 消息到好友/群"}}
    // {"type":"at", "to" :"23896218687@chatroom", "data": {"at":["wxid_xxxxxx","wxid_xxxxxxx"],"content": "{$@}消息到好友/群{$@}"}}
    public function send(Request $request){
        $bindUserId = auth()->id();
        if(!$bindUserId) {
            return [
                'success' => false,
                'message' => '用户绑定数据认证失败！请检查/重新生成token'
            ];
        }
        $wechatBot = WechatBot::where('user_id', $bindUserId)
            ->whereNotNull('client_id')
            ->whereNotNull('is_live_at')
            ->first();
        if(!$wechatBot) {
            return [
                'success' => false,
                'message' => '设备不在线,或改用户未绑定设备'
            ];
        }
        $wechatBot->send([$request['to']], $request->toArray());
        // 多条消息：第2条消息
        if(isset($request['addition'])){
            $wechatBot->send([$request['to']], $request['addition']);
        }
        return [
            'success' => true,
            'message' => '已提交设备发送',
        ];
    }

    // {"telephone":"13112345678"}
    public function add(Request $request){
        $wechatBot = WechatBot::where('user_id', auth()->id())
            ->whereNotNull('client_id')
            ->whereNotNull('login_at')
            ->first();
        if(!$wechatBot) {
            return [
                'success' => false,
                'message' => '设备不在线'
            ];
        }
        return $wechatBot->bot()->addFriendBySearch($request['telephone'], $request['message']??"Hi");
    }


    public function getFriends()
    {
        $wechatBot = WechatBot::where('user_id', auth()->id())->first();
        return $wechatBot->contacts()->paginate();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreWechatBotRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWechatBotRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WechatBot  $wechatBot
     * @return \Illuminate\Http\Response
     */
    public function show(WechatBot $wechatBot)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WechatBot  $wechatBot
     * @return \Illuminate\Http\Response
     */
    public function edit(WechatBot $wechatBot)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWechatBotRequest  $request
     * @param  \App\Models\WechatBot  $wechatBot
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWechatBotRequest $request, WechatBot $wechatBot)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WechatBot  $wechatBot
     * @return \Illuminate\Http\Response
     */
    public function destroy(WechatBot $wechatBot)
    {
        //
    }
}
