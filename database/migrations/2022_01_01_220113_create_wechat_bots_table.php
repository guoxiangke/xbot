<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_bots', function (Blueprint $table) {
            $table->id();

            //根据token就知道在哪台机器上，需要在admin的后台设置
            $table->string('token')->index()->comment('绑定的rootUser的token，需要后台配置');
            $table->foreignId('user_id')->uniqid()->comment('绑定的管理员user_id，需要后台配置,一个用户只允许绑定一个wx');
            $table->string('wxid')->index()->unique()->comment('绑定的box wxid，需要后台配置');
            //client_id动态变化
            $table->unsignedInteger('client_id')->nullable()->default(null)->comment('动态变换');

            $table->timestamp('login_at')->nullable()->default(null)->comment('null 代表已下线，用schedule检测is_live');
            $table->expires()->default(now()->addMonth(1))->comment('默认1个月内有效，超过需要付费');

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
        Schema::dropIfExists('wechat_bots');
    }
}
