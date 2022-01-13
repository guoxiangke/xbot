<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWechatMessageFileRequest;
use App\Http\Requests\UpdateWechatMessageFileRequest;
use App\Models\WechatMessageFile;

class WechatMessageFileController extends Controller
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
     * @param  \App\Http\Requests\StoreWechatMessageFileRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWechatMessageFileRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WechatMessageFile  $wechatMessageFile
     * @return \Illuminate\Http\Response
     */
    public function show(WechatMessageFile $wechatMessageFile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WechatMessageFile  $wechatMessageFile
     * @return \Illuminate\Http\Response
     */
    public function edit(WechatMessageFile $wechatMessageFile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWechatMessageFileRequest  $request
     * @param  \App\Models\WechatMessageFile  $wechatMessageFile
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWechatMessageFileRequest $request, WechatMessageFile $wechatMessageFile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WechatMessageFile  $wechatMessageFile
     * @return \Illuminate\Http\Response
     */
    public function destroy(WechatMessageFile $wechatMessageFile)
    {
        //
    }
}
