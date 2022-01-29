<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_messages', function (Blueprint $table) {
            $table->id();

            // 网页版主动发送信息时，seat_user_id为座席用户id， msgId 为null

            $table->unsignedTinyInteger('type')->comment('MT_RECV_TEXT_MSG：1');
            $table->foreignId('wechat_bot_id')->index()->comment('bot微信号');
            // 特殊：bot的陌生人(群友)发送消息到群
            $table->foreignId('from')->index()->nullable()->comment('消息发送者from:Null为bot发送的');
            $table->foreignId('conversation')->index()->comment('会话对象to:wechat_bot_contact_id');
            $table->text('content')->nullable()->comment('可识别的消息体');
            $table->string('msgid')->index()->comment('raw message id, 有可能会重复，选择最新的');
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
        Schema::dropIfExists('wechat_messages');
    }
}
