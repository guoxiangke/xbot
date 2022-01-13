<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatMessageFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_message_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wechat_bot_id')->index()->comment('bot微信号');
            $table->string('msgid')->index();
            $table->string('path')->nullable()->comment('Windows路径');
            $table->string('url')->nullable()->comment('文件链接');
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
        Schema::dropIfExists('wechat_message_files');
    }
}
