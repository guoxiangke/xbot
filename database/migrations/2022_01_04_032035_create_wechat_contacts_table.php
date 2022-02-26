<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('type')->default(1)->comment('0公众号，1联系人，2群');
            $table->string('wxid')->index()->unique(); //可以搜索
            // 如果是群，陌生人，只有一个wxid，其他都为空
            $table->string('nickname')->index()->default('')->nullable(); //可以搜索
            $table->string('avatar')->default('')->nullable(); // 可以为null

            $table->unsignedTinyInteger('sex')->default(0)->comment('0未知，1男，2女');
            $table->string('account')->default('')->nullable()->comment('');

            $table->string('country')->default('')->nullable()->comment('');
            $table->string('city')->default('')->nullable()->comment('');
            $table->string('province')->default('')->nullable()->comment('');

            $table->softDeletes();
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
        Schema::dropIfExists('wechat_contacts');
    }
}
