<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class Xbot {
    public $http; // PendingRequest
    private string $endPoint = '/';
    public int $clientId;
    public $botWxid;
    public $filePath; //发送文件所在windows位置

    //多台机器运行，每个机器多个bot登陆
    public function __construct($cliendUrl, $botWxid='null', $clientId=0, $filePath=""){
        $this->http = Http::withOptions([])
            ->acceptJson()
            ->baseUrl($cliendUrl)
            ->withoutVerifying();
        $this->clientId = $clientId;
        $this->botWxid = $botWxid;
        $this->filePath = $filePath;
    }

    private function request($type, $data = null){
    	$data = array_merge(['client_id'=> $this->clientId], get_defined_vars());
        // Log::debug("POST_RAW-" . $type, [$this->botWxid, $data]);
        // if(Str::contains($type, ['SEND'])) sleep(1);
        return rescue(fn() =>  $this->http->post($this->endPoint, $data), null, []);
    }

    public function getSelfInfo(){
    	$this->request('MT_DATA_OWNER_MSG');
    }

    public function quit(){
        $this->request('MT_QUIT_LOGIN_MSG');
    }

    public function loadQR(){
        $this->request('MT_RECV_QRCODE_MSG');
    }

    // 仅 MT_INJECT_WECHAT 不用 client id
    public function newClient(){
        $this->request('MT_INJECT_WECHAT');
    }

    public function closeClient($clientId=null){
        if($clientId){ // called by WechatClient::close($clientId); 不带botWxid，只传clienId的关闭方法
            $data = ['type'=>'MT_QUIT_WECHAT_MSG','client_id'=>$clientId];
            return rescue(fn() =>  $this->http->post($this->endPoint, $data), null, []);
        }else{ //called by $xbot->closeClient();
            $this->request('MT_QUIT_WECHAT_MSG');
        }
    }
    //$scene： 15:搜索手机号 14:通过群
    public function agreenFriend(int $scene, $v1, $v2){
    	$this->request('MT_ACCEPT_FRIEND_MSG', get_defined_vars());
    }

    public function refund($transferid){
        $this->request('MT_REFUSE_FRIEND_WCPAY_MSG', get_defined_vars());
    }

    public function addFriendBySearch($search){
        $this->request('MT_SEARCH_CONTACT_MSG', get_defined_vars());
    }

    // 通过微信搜索是3 手机号是15
    public function addFriendBySearchCallback($v1, $v2, $remark='hi', $source_type=3){
        $this->request('MT_ADD_SEARCH_CONTACT_MSG', get_defined_vars());
    }

    // >值描述
        // 0 正常状态(不是僵尸粉) 
        // 1 检测为僵尸粉(对方把我拉黑了) 
        // 2 检测为僵尸粉(对方把我从他的好友列表中删除了) 
        // 3 检测为僵尸粉(原因未知,如遇到3请反馈给我) 
    public function checkFriendShip($wxid){
        $this->request('MT_ZOMBIE_CHECK_MSG', get_defined_vars());
    }
    

    public function toVoiceText($msgid){
        $this->request('MT_TRANS_VOICE_MSG', get_defined_vars());
    }

    public function decryptImage($src_file, $dest_file, $size){
        sleep(ceil($size/1000000)+1);
        $this->request('MT_DECRYPT_IMG_MSG', get_defined_vars());
    }

    // 要发送的图片/文件，必须存放在 C:\Users\Public\Pictures\WeChat Files\wxid_???\FileStorage\File 里
    // test.txt => C:\Users\Public\Pictures\WeChat Files\wxid_???\FileStorage\File\\test.txt
    private function _getWinFile($file){
        return $this->filePath .'\\'. $this->botWxid."\FileStorage\File\\".$file;
    }
    
    public function sendFile($to_wxid, $file){
        $file = $this->_getWinFile($file);
        $this->request('MT_SEND_FILEMSG', get_defined_vars());
    }

    public function sendImage($to_wxid, $file){
        $file = $this->_getWinFile($file);
        $this->request('MT_SEND_IMGMSG', get_defined_vars());
    }

    public function getAllContacts(){
        $this->getFriends();
        $this->getRooms();
        $this->getPublics();
    }

    public function getFriends(){
    	$this->request('MT_DATA_FRIENDS_MSG');
    }

    public function getRooms(){
    	$this->request('MT_DATA_CHATROOMS_MSG');
    }

    public function getRoomMemembers($room_wxid){
    	$this->request('MT_DATA_CHATROOM_MEMBERS_MSG', get_defined_vars());
    }

    public function getPublics(){
    	$this->request('MT_DATA_PUBLICS_MSG');
    }

    //从网络更新群成员信息
	public function updateRoomMemembers($room_wxid){
    	$this->request('MT_UPDATE_ROOM_MEMBER_MSG', get_defined_vars());
    }


    public function sendText($to_wxid, $content){
        $this->request('MT_SEND_TEXTMSG', get_defined_vars());
    }

    public function sendContactCard($to_wxid, $card_wxid){
        $this->request('MT_SEND_CARDMSG', get_defined_vars());
    }

    // 没有返回callback，直接就修改成功了
    public function remark($wxid, $remark){
        $this->request('MT_MOD_FRIEND_REMARK_MSG', get_defined_vars());
    }

    //@人在群中
    // $content = "test,你好{$@},你好{$@}.早上好"
    // "at_list": ["wxid_xxxxxx","wxid_xxxxxxx"]
    public function sendAtText($to_wxid, $content, Array $at_list){
        // 判断如果不是群？
        if(Str::endsWith($to_wxid, '@chatroom')){
            $this->request('MT_SEND_CHATROOM_ATMSG', get_defined_vars());
        }else{
            $this->sendText($to_wxid, $content);
        }
    }

    public function sendLink($to_wxid, $url,
        $image_url='https://res.wx.qq.com/t/wx_fed/wechat-main-page/wechat-main-page-oversea-new/res/static/img/3ou3PnG.png',
        $title='test',
        $desc='desc'
    ){
        $this->request('MT_SEND_LINKMSG', get_defined_vars());
    }


    private function _sendXMLLink($xml, $to_wxid='filehelper'){
        return $this->request('MT_SEND_XMLMSG', get_defined_vars());
    }

    // $xbot->creatRoom('wxid1','wxid2','wxid3');
    public function creatRoom(...$wxids){
        // func_get_args()
        // 至少传3人
        if(count($wxids) < 3) return ['error'=>'至少传3人wxid才可以建群'];
        $this->request('MT_CREATE_ROOM_MSG', $wxids);
    }

    //直接入群
    public function addMememberToRoom($room_wxid, $who){
        $data = [
            "room_wxid"=>$room_wxid,
            'member_list'=>[$who]
        ];
        return $this->request('MT_INVITE_TO_ROOM_MSG', $data);
    }
    //TODO 需要维护一个群的成员数量在数据库中 MT_INVITE_TO_ROOM_MSG <40
    //邀请入群
    public function addMememberToRoomBig($room_wxid, $who){
        $data = [
            "room_wxid"=>$room_wxid,
            'member_list'=>[$who]
        ];
        return $this->request('MT_INVITE_TO_ROOM_REQ_MSG', $data);
    }


    public function deleteRoomMemember($room_wxid, $who){
        $data = [
            "room_wxid"=>$room_wxid,
            'member_list'=>[$who]
        ];
        return $this->request('MT_DEL_ROOM_MEMBER_MSG', $data);
    }

    public function autoAcceptTranster($transferid){
        return $this->request('MT_ACCEPT_WCPAY_MSG', get_defined_vars());
    }

    // MUSIC 必须备案域名
    public function sendMusic($to_wxid, $url, $title='', $desc=''){
        $botWxid = $this->botWxid;
        $xml = "<?xml version=\"1.0\"?><msg><appmsg appid=\"\" sdkver=\"0\"><title>$title</title><des>$desc</des><username /><action>view</action><type>3</type><showtype>0</showtype><content /><url>$url</url><lowurl>$url</lowurl><forwardflag>0</forwardflag><dataurl>$url</dataurl><lowdataurl /><contentattr>0</contentattr><streamvideo><streamvideourl /><streamvideototaltime>0</streamvideototaltime><streamvideotitle /><streamvideowording /><streamvideoweburl /><streamvideothumburl /><streamvideoaduxinfo /><streamvideopublishid /></streamvideo><canvasPageItem><canvasPageXml><![CDATA[]]></canvasPageXml></canvasPageItem><appattach><totallen>0</totallen><attachid /><cdnattachurl /><emoticonmd5></emoticonmd5><aeskey></aeskey><fileext /><islargefilemsg>0</islargefilemsg></appattach><extinfo /><androidsource>3</androidsource><thumburl /><mediatagname /><messageaction><![CDATA[]]></messageaction><messageext><![CDATA[]]></messageext><emoticongift><packageflag>0</packageflag><packageid /></emoticongift><emoticonshared><packageflag>0</packageflag><packageid /></emoticonshared><designershared><designeruin>0</designeruin><designername>null</designername><designerrediretcturl>null</designerrediretcturl></designershared><emotionpageshared><tid>0</tid><title>null</title><desc>null</desc><iconUrl>null</iconUrl><secondUrl /><pageType>0</pageType></emotionpageshared><webviewshared><shareUrlOriginal /><shareUrlOpen /><jsAppId /><publisherId /></webviewshared><template_id /><md5 /><weappinfo><username /><appid /><appservicetype>0</appservicetype><secflagforsinglepagemode>0</secflagforsinglepagemode><videopageinfo><thumbwidth>0</thumbwidth><thumbheight>0</thumbheight><fromopensdk>0</fromopensdk></videopageinfo></weappinfo><statextstr /><musicShareItem><musicDuration>0</musicDuration></musicShareItem><finderLiveProductShare><finderLiveID><![CDATA[]]></finderLiveID><finderUsername><![CDATA[]]></finderUsername><finderObjectID><![CDATA[]]></finderObjectID><finderNonceID><![CDATA[]]></finderNonceID><liveStatus><![CDATA[]]></liveStatus><appId><![CDATA[]]></appId><pagePath><![CDATA[]]></pagePath><productId><![CDATA[]]></productId><coverUrl><![CDATA[]]></coverUrl><productTitle><![CDATA[]]></productTitle><marketPrice><![CDATA[0]]></marketPrice><sellingPrice><![CDATA[0]]></sellingPrice><platformHeadImg><![CDATA[]]></platformHeadImg><platformName><![CDATA[]]></platformName><shopWindowId><![CDATA[]]></shopWindowId></finderLiveProductShare><findernamecard><username /><avatar><![CDATA[]]></avatar><nickname /><auth_job /><auth_icon>0</auth_icon><auth_icon_url /></findernamecard><finderGuarantee><scene><![CDATA[0]]></scene></finderGuarantee><directshare>0</directshare><gamecenter><namecard><iconUrl /><name /><desc /><tail /><jumpUrl /></namecard></gamecenter><patMsg><chatUser /><records><recordNum>0</recordNum></records></patMsg><secretmsg><issecretmsg>0</issecretmsg></secretmsg><websearch /></appmsg><fromusername>$botWxid</fromusername><scene>0</scene><appinfo><version>1</version><appname /></appinfo><commenturl /></msg>";
        return $this->_sendXMLLink($xml, $to_wxid);
    }
}
