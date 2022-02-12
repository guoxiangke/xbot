<?php

namespace Tests\Xbot;

use Tests\TestCase;

class XBotRemarkTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_xbot_Remark()
    {
        $wechatBot = \App\Models\WechatBot::find(1);
        $xbot = $wechatBot->xbot();
        $friend = 'bluesky_still'; //小永2 （机器人）
        $remark = 'A好友'.time();
        $xbot->remark($friend,$remark);
        sleep(6);
        $xbot->getFriends();
        sleep(5);

        // 如果没有记录所有群消息？
        $this->assertDatabaseHas('wechat_contacts', [
            'remark' => $remark,
        ]);
        // $this->seeInDatabase('users', ['email' => 'sally@example.com']);
        // $this->assertTrue(true);
    }
}
