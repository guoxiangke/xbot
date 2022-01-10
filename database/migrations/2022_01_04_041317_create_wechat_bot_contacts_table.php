<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatBotContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_bot_contacts', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('type')->default(3)->comment('0公众号，1联系人，2群, 3群陌生人');
            // bot和contact关系 N:N
            // @see https://laravel.com/docs/8.x/eloquent-relationships#many-to-many

            // https://www.codecheef.org/article/laravel-tips-to-set-foreign-key-in-laravel-migration
            $table->foreignId('wechat_bot_id')->index();
            $table->foreignId('wechat_contact_id')->index();
            $table->string('remark')->default('')->index()->comment('crm备注');//可以搜索
            $table->string('wxid')->index(); //可以搜索
            $table->foreignId('seat_user_id')->comment('转接/分配/负责的客服，默认为bot拥有者/管理者');
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
        Schema::dropIfExists('wechat_bot_contacts');
    }
}
