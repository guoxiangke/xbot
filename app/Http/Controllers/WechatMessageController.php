<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWechatMessageRequest;
use App\Http\Requests\UpdateWechatMessageRequest;
use App\Models\WechatMessage;

class WechatMessageController extends Controller
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
     * @param  \App\Http\Requests\StoreWechatMessageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWechatMessageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WechatMessage  $wechatMessage
     * @return \Illuminate\Http\Response
     */
    public function show(WechatMessage $wechatMessage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WechatMessage  $wechatMessage
     * @return \Illuminate\Http\Response
     */
    public function edit(WechatMessage $wechatMessage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWechatMessageRequest  $request
     * @param  \App\Models\WechatMessage  $wechatMessage
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWechatMessageRequest $request, WechatMessage $wechatMessage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WechatMessage  $wechatMessage
     * @return \Illuminate\Http\Response
     */
    public function destroy(WechatMessage $wechatMessage)
    {
        //
    }
}
