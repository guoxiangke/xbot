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
            $table->unsignedTinyInteger('type')->default(0)->comment('0公众号，1联系人，2群');
            $table->string('wxid')->index(); //可以搜索
            $table->string('nickname')->index(); //可以搜索
            $table->string('remark')->index(); //可以搜索
            $table->string('avatar');

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
