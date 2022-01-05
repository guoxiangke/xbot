<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWechatContactRequest;
use App\Http\Requests\UpdateWechatContactRequest;
use App\Models\WechatContact;

class WechatContactController extends Controller
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
     * @param  \App\Http\Requests\StoreWechatContactRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWechatContactRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WechatContact  $wechatContact
     * @return \Illuminate\Http\Response
     */
    public function show(WechatContact $wechatContact)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WechatContact  $wechatContact
     * @return \Illuminate\Http\Response
     */
    public function edit(WechatContact $wechatContact)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWechatContactRequest  $request
     * @param  \App\Models\WechatContact  $wechatContact
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWechatContactRequest $request, WechatContact $wechatContact)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WechatContact  $wechatContact
     * @return \Illuminate\Http\Response
     */
    public function destroy(WechatContact $wechatContact)
    {
        //
    }
}
