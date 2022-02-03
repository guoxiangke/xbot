<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXbotSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xbot_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wechat_bot_id')->index()->default(0)->comment('谁发的');//含在下面
            $table->foreignId('wechat_bot_contact_id')->index()->comment('发给谁');//订阅联系人/群
            $table->string('keyword')->comment('订阅的资源关键字');
            $table->unsignedInteger('price')->default(0)->comment('实际订阅价格，单位分');
            $table->foreignId('wechat_pay_order_id')->nullable();
            // 高级会员订阅,自定义发送时间
            $table->string('cron')->default('0 7 * * *');
            $table->softDeletes(); //失效时间：取消订阅或订阅结束  //done or cancled
            $table->timestamps(); //生效时间
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xbot_subscriptions');
    }
}
