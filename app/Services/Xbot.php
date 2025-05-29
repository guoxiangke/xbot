<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

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


    public function sendImageUrl($to_wxid, $url){
        $this->request('MT_SEND_IMGMSG_BY_URL', get_defined_vars());
    }
    
    // TODO
    public function sendFileUrl($to_wxid, $url){
        $this->request('MT_SEND_FILE_BY_URL', get_defined_vars());
    }

    public function getAllContacts(){
        $this->getFriends();
        $this->getRooms();
        $this->getPublics();
    }

    public function getFriends(){
    	$this->request('MT_DATA_FRIENDS_MSG');
    }

    public function getFriend($wxid){
        return $this->request('MT_DATA_WXID_MSG', compact('wxid'));
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
        $image_url='https://mmecoa.qpic.cn/sz_mmecoa_png/dTE2nNAecJYUksGb1XOwruv2rxedibHdN7j0cgcpw8DibwhS23UGjnu9QibULUSfyjtINNticX4saqZ8cYRJmUHFeQ/640?wx_fmt=png&amp;from=appmsg';
        $this->request('MT_SEND_LINKMSG', get_defined_vars());
    }


    public function _sendXMLLink($xml, $to_wxid='filehelper'){
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

   //   "location" => [
   //     "@attributes" => [
   //       "x" => "34.189342",
   //       "y" => "105.187981",
   //       "scale" => "16",
   //       "label" => "甘肃省陇南市礼县秦礼时代广场一楼周大福珠宝店",
   //       "maptype" => "roadmap",
   //       "poiname" => "周大福(礼秦礼时代广场店)",
   //       "poiid" => "",
   //     ],

    public function sendLocation($to_wxid, $x, $y, $scale, $label, $maptype, $poiname, $poiid){
        $xml = "<?xml version=\"1.0\"?>\n<msg><location x=\"$x\" y=\"$y\" scale=\"$scale\" label=\"$label\" maptype=\"$maptype\" poiname=\"$poiname\" poiid=\"$poiid\" />\n</msg>\n";
        return $this->_sendXMLLink($xml, $to_wxid);
    }

    public function forward($to_wxid, $msgid){
        return $this->request('MT_FORWARD_ANY_MSG', get_defined_vars());
    }

    public function snsList($max_id=0){
        return $this->request('MT_SNS_TIMELINE_MSG', get_defined_vars());
    }
    public function snsLike($object_id){
        return $this->request('MT_SNS_LIKE_MSG', get_defined_vars());
    }
    public function snsComment($object_id, $content="评论内容"){
        return $this->request('MT_SNS_COMMENT_MSG', get_defined_vars());
    }
    // "<TimelineObject><id>13917603808309153885</id><user ..."
    public function snsPublish($object_desc){
        return $this->request('MT_SNS_SEND_MSG', get_defined_vars());
    }

    // 朋友圈转发视频号
    // 朋友圈发视频 竖屏 :720 x 1280 横屏：1152 x 720
    // 需要2张 图片作为封面！
    // contentUrl 可以来做广告！ support
    public function sendVideoPost($title, $url, $thumbImgUrl='https://img9.doubanio.com/view/puppy_image/raw/public/1771365ca98ig9er706.jpg'){
        $msg = '<TimelineObject><id>1234567890</id><username>0000</username><createTime>1661025740</createTime><contentDesc>'.$title.'</contentDesc><contentDescShowType>0</contentDescShowType><contentDescScene>0</contentDescScene><private>0</private><sightFolded>0</sightFolded><showFlag>0</showFlag><appInfo><id></id><version></version><appName></appName><installUrl></installUrl><fromUrl></fromUrl><isForceUpdate>0</isForceUpdate></appInfo><sourceUserName></sourceUserName><sourceNickName></sourceNickName><statisticsData></statisticsData><statExtStr></statExtStr><ContentObject><contentStyle>15</contentStyle><title>0000微信小视频</title><description>Sight</description><mediaList><media><id>13933693810826481954</id><type>6</type><title></title><description>0000视频发送</description><private>0</private><userData></userData><subType>0</subType><videoSize width="720" height="1280"></videoSize><url type="1" md5="" videomd5="">'.$url.'</url><thumb type="1">'.$thumbImgUrl.'</thumb><size width="720" height="1280" totalSize="000"></size><videoDuration>2.035011</videoDuration></media></mediaList><contentUrl>https://support.weixin.qq.com/cgi-bin/mmsupport-bin/readtemplate?t=page/common_page__upgrade&amp;v=1</contentUrl></ContentObject><actionInfo><appMsg><messageAction></messageAction></appMsg></actionInfo><location poiClassifyId="" poiName="" poiAddress="" poiClassifyType="0" city=""></location><publicUserName></publicUserName><streamvideo><streamvideourl></streamvideourl><streamvideothumburl></streamvideothumburl><streamvideoweburl></streamvideoweburl></streamvideo></TimelineObject>';
        return $this->snsPublish($msg);
    }

    // 朋友圈转发9宫格 <mediaList><media>
    public function sendImagesPost($title, $urls){
        $media = '';
        foreach ($urls as $url) {
            $media .= '<media><id>13933920070604632377</id><type>2</type><title></title><description></description><private>0</private><userData></userData><subType>0</subType><videoSize width="0" height="0"></videoSize><url type="1" md5="2" videomd5="">'.$url.'</url><thumb type="1">'.$url.'</thumb><size width="1024.000000" height="943.000000" totalSize="16001"></size></media>';
        }
        $msg = '<TimelineObject><id><![CDATA[13933541731974386031]]></id><username>0000</username><createTime><![CDATA[1661007610]]></createTime><contentDescShowType>0</contentDescShowType><contentDescScene>0</contentDescScene><private><![CDATA[0]]></private><contentDesc><![CDATA['.$title.']]></contentDesc><contentattr><![CDATA[0]]></contentattr><sourceUserName></sourceUserName><sourceNickName></sourceNickName><statisticsData></statisticsData><weappInfo><appUserName></appUserName><pagePath></pagePath><version><![CDATA[0]]></version><debugMode><![CDATA[0]]></debugMode><shareActionId></shareActionId><isGame><![CDATA[0]]></isGame><messageExtraData></messageExtraData><subType><![CDATA[0]]></subType><preloadResources></preloadResources></weappInfo><canvasInfoXml></canvasInfoXml><ContentObject><contentStyle><![CDATA[1]]></contentStyle><contentSubStyle><![CDATA[0]]></contentSubStyle><title></title><description></description><contentUrl></contentUrl><mediaList>'.$media.'</mediaList></ContentObject><actionInfo><appMsg><mediaTagName></mediaTagName><messageExt></messageExt><messageAction></messageAction></appMsg></actionInfo><appInfo><id></id></appInfo><location poiClassifyId="" poiName="" poiAddress="" poiClassifyType="0" city=""></location><publicUserName></publicUserName><streamvideo><streamvideourl></streamvideourl><streamvideothumburl></streamvideothumburl><streamvideoweburl></streamvideoweburl></streamvideo></TimelineObject>';
        return $this->snsPublish($msg);
    }


    // 朋友圈转发链接
    public function sendLinkPost($title, $url, $comment=''){
        $msg = '<TimelineObject><id>13933661134568034593</id><username>000</username><createTime>1661021844</createTime><contentDesc>'. $comment. '</contentDesc><contentDescShowType>0</contentDescShowType><contentDescScene>4</contentDescScene><private>0</private><sightFolded>0</sightFolded><showFlag>0</showFlag><appInfo><id></id><version></version><appName></appName><installUrl></installUrl><fromUrl></fromUrl><isForceUpdate>0</isForceUpdate></appInfo><sourceUserName></sourceUserName><sourceNickName></sourceNickName><statisticsData></statisticsData><statExtStr></statExtStr><ContentObject><contentStyle>3</contentStyle><title>'. $title. '</title><description></description><contentUrl>'. $url. '</contentUrl><mediaList></mediaList></ContentObject><actionInfo><appMsg><messageAction></messageAction></appMsg></actionInfo><location poiClassifyId="" poiName="" poiAddress="" poiClassifyType="0" city=""></location><publicUserName></publicUserName><streamvideo><streamvideourl></streamvideourl><streamvideothumburl></streamvideothumburl><streamvideoweburl></streamvideoweburl></streamvideo></TimelineObject>';
        return $this->snsPublish($msg);
    }

    // 朋友圈转发音乐消息
    public function sendMusicPost($title, $url, $description='朋友圈音乐消息描述', $comment='这是描述', $thumbImgUrl='http://mmsns.c2c.wechat.com/mmsns/vRn02nrlYphiaibib27nbILHxvsD6UjZvclzGREZFciaFCmDt9jdhbHu7tL2DiaGjhGh61ibDauiaQWsIU/150'){
        $msg = '<TimelineObject><id><![CDATA[000]]></id><username><![CDATA[wxid_t36o5djpivk312]]></username><createTime><![CDATA[1661054193]]></createTime><contentDesc>'. $comment. '</contentDesc><contentDescShowType>0</contentDescShowType><contentDescScene>0</contentDescScene><private><![CDATA[0]]></private><contentDesc></contentDesc><contentattr><![CDATA[0]]></contentattr><sourceUserName></sourceUserName><sourceNickName></sourceNickName><statisticsData></statisticsData><weappInfo><appUserName></appUserName><pagePath></pagePath><version><![CDATA[0]]></version><debugMode><![CDATA[0]]></debugMode><shareActionId></shareActionId><isGame><![CDATA[0]]></isGame><messageExtraData></messageExtraData><subType><![CDATA[0]]></subType><preloadResources></preloadResources></weappInfo><canvasInfoXml></canvasInfoXml><ContentObject><contentStyle><![CDATA[4]]></contentStyle><contentSubStyle><![CDATA[0]]></contentSubStyle><title></title><description></description><contentUrl><![CDATA['. $url. ']]></contentUrl><mediaList><media><id><![CDATA[00]]></id><type><![CDATA[3]]></type><title><![CDATA['. $title. ']]></title><description><![CDATA[点击▶️收听  '. $description. ']]></description><private><![CDATA[0]]></private><url type="0"><![CDATA['. $url. ']]></url><thumb type="1"><![CDATA['. $thumbImgUrl. ']]></thumb><videoDuration><![CDATA[0.0]]></videoDuration><lowBandUrl type="0"><![CDATA['. $url. ']]></lowBandUrl><size totalSize="342.0" width="45.0" height="45.0"></size></media></mediaList></ContentObject><actionInfo><appMsg><mediaTagName></mediaTagName><messageExt></messageExt><messageAction></messageAction></appMsg></actionInfo><appInfo><id></id></appInfo><location poiClassifyId="" poiName="" poiAddress="" poiClassifyType="0" city=""></location><publicUserName></publicUserName><streamvideo><streamvideourl></streamvideourl><streamvideothumburl></streamvideothumburl><streamvideoweburl></streamvideoweburl></streamvideo></TimelineObject>';
        return $this->snsPublish($msg);
    }
    // 朋友圈转发QQ音乐消息
    public function sendQQMusicPost($title, $url, $description='朋友圈QQ音乐消息描述', $comment='这是描述', $thumbImgUrl='http://shmmsns.qpic.cn/mmsns/R7EkLqfbhZdGKk2iaqGLC9qx1Hpgghjrict51nrzjDhvI4Q0k6ctac1Wia0OlbEdTu6IHtBicRVThVw/150'){
        $msg = '<TimelineObject><id>111</id><username>000</username><createTime>1662997334</createTime><contentDesc>'. $comment. '</contentDesc><contentDescShowType>0</contentDescShowType><contentDescScene>4</contentDescScene><private>0</private><sightFolded>0</sightFolded><showFlag>0</showFlag><appInfo><id>wx5aa333606550dfd5</id><version>53</version><appName></appName><installUrl></installUrl><fromUrl></fromUrl><isForceUpdate>0</isForceUpdate><clickable>0</clickable></appInfo><sourceUserName></sourceUserName><sourceNickName></sourceNickName><statisticsData></statisticsData><statExtStr>GhQKEnd4NWFhMzMzNjA2NTUwZGZkNQ==</statExtStr><ContentObject><musicShareItem><mvSingerName>'. $description. '</mvSingerName><musicDuration>261877</musicDuration></musicShareItem><contentStyle>42</contentStyle><title></title><description></description><contentUrl>http://music.163.com/song/1938392288/?userid=1577032097</contentUrl><mediaList><media><id>3333</id><type>3</type><title>'. $title. '</title><description>444</description><private>0</private><userData></userData><subType>0</subType><videoSize width="0" height="0"></videoSize><url type="0" md5="" videomd5="">'. $url. '</url><lowBandUrl type="0">'. $url. '</lowBandUrl><thumb type="1">'. $thumbImgUrl. '</thumb></media></mediaList><musicShareItem><mvSingerName>吉拉朵5555</mvSingerName><musicDuration>261877</musicDuration></musicShareItem></ContentObject><actionInfo><scene>0</scene><type>0</type><url></url><appMsg><mediaTagName></mediaTagName><messageExt></messageExt><messageAction></messageAction><appid>wx5aa333606550dfd5</appid></appMsg><newWordingKey></newWordingKey><newtype>0</newtype><installedWording></installedWording><uninstalledWording></uninstalledWording></actionInfo><location poiClassifyId="" poiName="" poiAddress="" poiClassifyType="0" city=""></location><publicUserName></publicUserName><streamvideo><streamvideourl></streamvideourl><streamvideothumburl></streamvideothumburl><streamvideoweburl></streamvideoweburl></streamvideo></TimelineObject>';
        return $this->snsPublish($msg);
    }

    public function sendMusic($to_wxid, $url, $title='', $desc='',$coverUrl=null, $lrc=null){
        if($this->botWxid == 'keke302'){
            $coverUrl='https://mmecoa.qpic.cn/sz_mmecoa_png/dTE2nNAecJYUksGb1XOwruv2rxedibHdN7j0cgcpw8DibwhS23UGjnu9QibULUSfyjtINNticX4saqZ8cYRJmUHFeQ/640?wx_fmt=png&amp;from=appmsg'; // 小永个人号
            return $this->sendMusicWithCoverAndLrc($to_wxid, $url, $title, $desc,$coverUrl, $lrc);
        }
        $app = $this->getRandomApp();
        $botWxid = $this->botWxid;
        $xml = "<?xml version=\"1.0\"?><msg><appmsg appid={$app['id']} sdkver=\"0\"><title>$title</title><des>$desc</des><type>3</type><action>view</action><url></url><dataurl>$url</dataurl><appattach><cdnthumburl></cdnthumburl><cdnthumbmd5></cdnthumbmd5><cdnthumblength>33097</cdnthumblength><cdnthumbwidth>120</cdnthumbwidth><cdnthumbheight>120</cdnthumbheight><cdnthumbaeskey>086a10f766857ea8849cb7335dcd24de</cdnthumbaeskey><aeskey>086a10f766857ea8849cb7335dcd24de</aeskey><encryver>0</encryver><filekey>wxid_t36o5djpivk312_1062_1683068786</filekey></appattach><md5></md5><statextstr>GhQKEnd4ZjI5ZjczYzJjNDQxMjk1ZQ==</statextstr><webviewshared><jsAppId><![CDATA[]]></jsAppId></webviewshared><recorditem><![CDATA[(null)]]></recorditem><uploadpercent>99</uploadpercent><secretmsg><isscrectmsg>0</isscrectmsg></secretmsg></appmsg><fromusername>$botWxid</fromusername><scene>0</scene><appinfo><version>132</version><appname>{$app['name']}</appname></appinfo><commenturl /></msg>";
        return $this->_sendXMLLink($xml, $to_wxid);
    }

    public function sendMusicWithCoverAndLrc($to_wxid, $url, $title='', $desc='', $coverUrl=null, $lrc=null){
        $app = $this->getRandomApp();
        $botWxid = $this->botWxid;
        $appId=$app['id'];
        $xml = "<?xml version=\"1.0\"?>\n<msg><appmsg appid=\"{$appId}\" sdkver=\"0\"><title>{$title}</title><des>{$desc}</des><type>3</type><action>view</action><dataurl>{$url}</dataurl><thumburl>{$coverUrl}</thumburl><songlyric>{$lrc}</songlyric><appattach><cdnthumbaeskey /><aeskey /></appattach><webviewshared><jsAppId><![CDATA[]]></jsAppId></webviewshared><mpsharetrace><hasfinderelement>0</hasfinderelement></mpsharetrace><secretmsg><isscrectmsg>0</isscrectmsg></secretmsg></appmsg><fromusername>{$botWxid}</fromusername><scene>0</scene><appinfo><version>29</version><appname>{$app['name']}</appname></appinfo><commenturl></commenturl>\n</msg>\n";
        return $this->_sendXMLLink($xml, $to_wxid);
    }

    public function getRandomApp(){
        return ['name'=> '微信电脑版',    'id' => 'wx6618f1cfc6c132f8'];
        return Arr::random([
            ['name'=>'订阅号助手',    'id'=>'wx50a3272e1669f0c0'],
            ['name'=>'QQ音乐', 'id'=>'wx5aa333606550dfd5'],
            ['name'=>'网易云音乐',    'id'=>'wx8dd6ecd81906fd84'],
            ['name'=>'摇一摇',  'id'=>'wx485a97c844086dc9'],
            ['name'=>'微信电脑版',    'id'=>'wx6618f1cfc6c132f8'],
            // ['name'=> '良友聆听',   'id' => 'wxf29f73c2c441295e'],
        ]);
    }
    
}
