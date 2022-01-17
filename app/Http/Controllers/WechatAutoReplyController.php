<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWechatAutoReplyRequest;
use App\Http\Requests\UpdateWechatAutoReplyRequest;
use App\Models\WechatAutoReply;

class WechatAutoReplyController extends Controller
{
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
     * @param  \App\Http\Requests\StoreWechatAutoReplyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWechatAutoReplyRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WechatAutoReply  $wechatAutoReply
     * @return \Illuminate\Http\Response
     */
    public function show(WechatAutoReply $wechatAutoReply)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WechatAutoReply  $wechatAutoReply
     * @return \Illuminate\Http\Response
     */
    public function edit(WechatAutoReply $wechatAutoReply)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWechatAutoReplyRequest  $request
     * @param  \App\Models\WechatAutoReply  $wechatAutoReply
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWechatAutoReplyRequest $request, WechatAutoReply $wechatAutoReply)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WechatAutoReply  $wechatAutoReply
     * @return \Illuminate\Http\Response
     */
    public function destroy(WechatAutoReply $wechatAutoReply)
    {
        //
    }
}
