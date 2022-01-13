<?php

namespace App\Http\Livewire;

use Livewire\Component;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\WechatBot;
use App\Models\WechatBotContact;

class Wechat extends Component
{
    public function render()
    {
        return view('livewire.wechat');
    }

    public string $defaultAvatar = WechatBotContact::DEFAULT_AVATAR; // fallback
    public $wechatBot;
    public $xbotInfo;
    public $loginAt;


    

    

    public $config;

    public $isBind = false;
    public $isLive = false;
    public $loginQr = false;
    public $msg;
    public function mount()
    {
        $wechatBot = WechatBot::whereUserId(auth()->id())->first();
        if(!$wechatBot) {
            $this->isBind = false;
            $this->msg = '当前账户暂未绑定wxid, 请与管理员联系！';
            return;
        }
        $wechatBot->isLive(); //检测是否在线？
        $wechatBot->refresh();
        $this->wechatBot = $wechatBot;

        // $wechatBot->removeMeta('xbot');
        $this->xbotInfo = $wechatBot->getMeta('xbot', [
            'account' => 'account',
            'avatar' => 'avatar',
            'nickname' => 'nickname',
            'wxid' => 'wxid',
        ]);

        $this->isLive = $wechatBot->is_live_at;
        if(!$this->isLive){
            $cacheKey = $wechatBot->wechat_client_id;
            $qrPool = Cache::get("xbots.{$cacheKey}.qrPool", []);
            if($qrPool){
                $qrWithCliendId = array_shift($qrPool);
                $loginQr = 'https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=' . $qrWithCliendId['qr'];
                $this->loginQr = $loginQr;
                $this->xbotInfo['avatar'] = $loginQr;
                $this->msg = '请使用账户绑定的wxid,扫码登陆！';
            }
        }
        $this->loginAt = optional($wechatBot->login_at)->diffForHumans();

        // $wechatBot->removeMeta('xbot.config');
        $this->config = $wechatBot->getMeta('xbot.config', [
            'isAutoWcpay' => false, // MT_RECV_WCPAY_MSG
            'isAutoAgree' => false, //自动同意好友请求
            'isWelcome' => false,
            'weclomeMsg' => 'hi',
            'isListenRoom' => false,
            'isListenRoomAll' => false,
            'isWebhook' => false,
            'webhookUrl' => 'http://192.168.168.117/api/webhook/xbot',
            'webhookSecret' => '123456',
        ]);

    }
    public function updated($name, $value)
    {
        if(in_array($name, [
            'config.isAutoWcpay',
            'config.isAutoAgree',
            'config.isWelcome',
            'config.weclomeMsg',
            'config.isListenRoom',
            'config.isListenRoomAll',
            'config.isWebhook',
            'config.webhookUrl',
            'config.webhookSecret',
            ])
        ){
            $key = str_replace('config.', '', $name);
            $this->config[$key] =  $value;
            $this->wechatBot->setMeta('xbot.config', $this->config);
        }
    }


    public function logout()
    {
        $this->wechatBot->logout();
        sleep(1);
        return redirect()->to('/channels/wechat');
    }
}
