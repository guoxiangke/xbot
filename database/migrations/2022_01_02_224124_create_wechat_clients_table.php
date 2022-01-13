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
            $table->foreignId('wechat_client_id')->index();
            $table->string('location')->comment('Windows机器暴露的xbot');
            $table->string('silk')->comment('Windows机器暴露的语音临时文件');
            $table->string('file')->comment('Windows机器暴露的Wechat Files文件夹');
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
