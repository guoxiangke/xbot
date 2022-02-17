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
            $table->string('token')->comment('Windows机器标识');
            $table->string('xbot')->comment('Windows机器暴露的xbot');
            $table->string('file')->comment('Windows机器暴露的Wechat Files文件夹');
            $table->string('file_path')->nullable()->default("C:\\Users\\Public\\Pictures\\WeChat Files");
            $table->string('silk')->comment('Windows机器暴露的语音临时文件');
            $table->string('silk_path')->nullable()->default("C:\\Users\\Administrator\\AppData\\Local\\Temp");
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
