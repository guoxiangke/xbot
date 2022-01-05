<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWechatBotContactRequest;
use App\Http\Requests\UpdateWechatBotContactRequest;
use App\Models\WechatBotContact;

class WechatBotContactController extends Controller
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
     * @param  \App\Http\Requests\StoreWechatBotContactRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWechatBotContactRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WechatBotContact  $wechatBotContact
     * @return \Illuminate\Http\Response
     */
    public function show(WechatBotContact $wechatBotContact)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WechatBotContact  $wechatBotContact
     * @return \Illuminate\Http\Response
     */
    public function edit(WechatBotContact $wechatBotContact)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWechatBotContactRequest  $request
     * @param  \App\Models\WechatBotContact  $wechatBotContact
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWechatBotContactRequest $request, WechatBotContact $wechatBotContact)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WechatBotContact  $wechatBotContact
     * @return \Illuminate\Http\Response
     */
    public function destroy(WechatBotContact $wechatBotContact)
    {
        //
    }
}
