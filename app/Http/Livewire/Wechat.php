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

    public function getListeners(){
        if(!$this->wechatBot) return [];
        return [
            "echo-private:xbot.login.{$this->wechatBot->id},WechatBotLogin" => 'redirectPage',
        ];
    }

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
        if ($wechatBot->expired()) {
            $this->msg = '当前账户已过期, 随时可能会强制下线，请及时管理员联系！';
        }
        // $wechatBot->isLive(); //检测是否在线？
        // $wechatBot->refresh();
        $this->wechatBot = $wechatBot;

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

        $this->config = $wechatBot->getMeta('xbot.config', [
            'isAutoWcpay' => false, // MT_RECV_WCPAY_MSG
            'isAutoAgree' => false, //自动同意好友请求
            'isWelcome' => false,
            'weclomeMsg' => 'hi',
            'isListenRoom' => false,
            'isListenRoomAll' => false,
            'isWebhook' => false,
            'webhookUrl' => 'http://192.168.168.117/api/webhook/xbot',
            'webhookUrl2' => 'http://192.168.168.117/api/webhook/xbot',
            'webhookSecret' => '123456',
            'isAutoReply' => false, // 关键词自动回复
            'isResourceOn' => false, // x-resources资源自动回复
            'isIrcOn' => false, // AI自动回复
        ]);
        if(!isset($this->config['isResourceOn'])){
            $this->config['isResourceOn'] = false;
        }
        if(!isset($this->config['isIrcOn'])){
            $this->config['isIrcOn'] = false;
        }

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
            'config.webhookUrl2',
            'config.webhookSecret',
            'config.isAutoReply',
            'config.isResourceOn',
            'config.isIrcOn',
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
        return redirect()->to('/channels/wechat');
    }
    public function redirectPage()
    {
        return redirect()->to('/channels/wechat');
    }
}
