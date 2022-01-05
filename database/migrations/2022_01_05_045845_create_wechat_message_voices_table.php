<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatMessageVoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_message_voices', function (Blueprint $table) {
            $table->id();
            $table->string('msgid')
                ->index();
                // ->references('msgid')
                // ->on('wechat_messages')
                // ->onDelete('cascade')
                // ->comment('语音消息msgid');
            // TODO 如果messages删除了，这个voices肯定得删除！
                // https://dev.to/rafaelfranca/cascading-on-update-and-on-delete-in-migration-2jad
                // ->constrained()->cascadeOnDelete();
            $table->string('content')->nullable()->comment('语音转文字');
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
        Schema::dropIfExists('wechat_message_voices');
    }
}
