<?php

namespace Tests\Xbot;

use Tests\TestCase;

class XBotSendTextToGroupTest extends TestCase
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
    public function test_xbot_sendTextToGroup()
    {
        $wechatBot = \App\Models\WechatBot::find(1);
        $xbot = $wechatBot->xbot();
        $group = '23896218687@chatroom'; // xbot群
        $text = "test_xbot_sendTextToGroup ".time();
        $xbot->sendText($group, $text);
        sleep(3);

        // 如果没有记录所有群消息？
        $this->assertDatabaseHas('wechat_messages', [
            'content' => $text,
        ]);
        // $this->seeInDatabase('users', ['email' => 'sally@example.com']);
        // $this->assertTrue(true);
    }
}
