<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_clients', function (Blueprint $table) {
            $table->id();
            $table->string('token')->comment('绑定的rootUser的token，需要后台配置');
            $table->string('location')->comment('Windows机器位置');
            $table->unsignedInteger('clients')->comment('可登陆几个微信');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wechat_clients');
    }
}