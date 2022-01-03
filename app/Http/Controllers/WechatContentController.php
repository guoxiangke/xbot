<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWechatContentRequest;
use App\Http\Requests\UpdateWechatContentRequest;
use App\Models\WechatContent;

class WechatContentController extends Controller
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
     * @param  \App\Http\Requests\StoreWechatContentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWechatContentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WechatContent  $wechatContent
     * @return \Illuminate\Http\Response
     */
    public function show(WechatContent $wechatContent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WechatContent  $wechatContent
     * @return \Illuminate\Http\Response
     */
    public function edit(WechatContent $wechatContent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWechatContentRequest  $request
     * @param  \App\Models\WechatContent  $wechatContent
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWechatContentRequest $request, WechatContent $wechatContent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WechatContent  $wechatContent
     * @return \Illuminate\Http\Response
     */
    public function destroy(WechatContent $wechatContent)
    {
        //
    }
}
