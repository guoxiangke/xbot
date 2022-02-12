<?php

namespace Tests\Xbot;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class XBotApiSendTextToFriend extends TestCase
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
    public function test_xbot_ApiSendTextToFriend()
    {
        $token ='Y4e1qJPKSLtrd7vn7t9cvJrMQPz3zP7xQ4BtsQVY'; // （AI机器人)
        $friend = 'bluesky_still'; //小永2

        $text = "API1主动发送 文本 到 个人".now();
        $res = Http::withToken($token)
            ->post(config('xbot.test_endpoint'), [
            "type"=>"text",
            "to"=> $friend,
            "data"=> [
                "content"=> $text
            ]
        ]);
        sleep(5);
        $this->assertDatabaseHas('wechat_messages', [
            'content' => $text,
        ]);
    }
}
