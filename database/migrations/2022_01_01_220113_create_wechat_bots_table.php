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
            $table->foreignId('wechat_client_id')->index();
            $table->foreignId('user_id')->uniqid()->comment('绑定的管理员user_id，需要后台配置,一个用户只允许绑定一个wx');
            $table->string('wxid')->index()->unique()->comment('绑定的box wxid，需要后台配置');
            $table->string('name')->nullable()->comment('bot名字remark描述');
            //client_id动态变化
            $table->unsignedInteger('client_id')->nullable()->default(null)->comment('动态变换');

            $table->timestamp('login_at')->nullable()->default(now())->comment('null 代表已下线，用schedule检测is_live');

            // 程序崩溃时，login_at 还在，咋办？ 
            // 每小时发送 is_live 命令给命令助手，如果在线，更新 live_at 为当前时间。
            // 每小时+1分钟 check，如果 live_at diff now > 3分钟，则代表已崩溃离线，需要手动重启，发信息给管理员
            $table->timestamp('is_live_at')->nullable()->default(now())->comment('last_check_live_at');
            $table->timestamp('expires_at')->nullable()->default(now()->addMonth(1))->comment('默认1个月内有效，超过需要付费');

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
