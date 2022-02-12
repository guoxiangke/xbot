<?php

namespace Tests\Xbot;

use Tests\TestCase;

class XBotSendTextToFriendTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        // $this->artisan('migrate:fresh --force --seed --env=testing');
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_xbot_sendTextToFriend()
    {
        $wechatBot = \App\Models\WechatBot::find(1);
        $xbot = $wechatBot->xbot();
        $friend = 'bluesky_still'; //小永2 （机器人）
        $text = "test_xbot_sendTextToFriend ".time();
        $xbot->sendText($friend, $text);
        sleep(3);

        $this->assertDatabaseHas('wechat_messages', [
            'content' => $text,
        ]);
        // $this->assertTrue(true);
    }
}
