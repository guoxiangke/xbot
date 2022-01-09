<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWechatClientRequest;
use App\Http\Requests\UpdateWechatClientRequest;
use App\Models\WechatClient;

class WechatClientController extends Controller
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
     * @param  \App\Http\Requests\StoreWechatClientRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWechatClientRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WechatClient  $wechatClient
     * @return \Illuminate\Http\Response
     */
    public function show(WechatClient $wechatClient)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WechatClient  $wechatClient
     * @return \Illuminate\Http\Response
     */
    public function edit(WechatClient $wechatClient)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWechatClientRequest  $request
     * @param  \App\Models\WechatClient  $wechatClient
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWechatClientRequest $request, WechatClient $wechatClient)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WechatClient  $wechatClient
     * @return \Illuminate\Http\Response
     */
    public function destroy(WechatClient $wechatClient)
    {
        //
    }
}
