<?php

namespace Tests\Xbot;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class XBotSendCardToGroupTest extends TestCase
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
    public function test_xbot_sendCardToFriend()
    {
        $wechatBot = \App\Models\WechatBot::find(1);
        $xbot = $wechatBot->xbot();
        $group = '23896218687@chatroom'; // xbot群
        // $text = "test_xbot_sendTextToFriend ".time();
        // $xbot->sendText($friend, $text);
        $url = 'http://wx.qlogo.cn/mmhead/ver_1/hEEdPibI93Cv1ICziccahUuAhzf7K07icjlT2rribdZT0FqV7fXAO61zQibPEzROsvEfTKwGozRyf4hjmNLspBpSiaze7PIibmHspf19JAEKsnUGAA/132';
        $urltestUnique = "https://xx.com?time=".now();
        $xbot->sendLink($group, $urltestUnique,
            $url,
            $title='欢迎青尤～',
            $desc="邀请人：可爱猫°小黑\n———————————\n☆小宝贝☆"
        );
        sleep(3);

        $key = "xbot-test-link";
        $value = Cache::get($key, false);
        $this->assertEquals($urltestUnique, $value);
    }
}
