<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Plank\Metable\Metable;
// use Spatie\Activitylog\Traits\LogsActivity;
use Mvdnbrk\EloquentExpirable\Expirable;
use App\Services\Xbot;
use App\Models\User;
use App\Models\WechatBotContact;
use App\Events\WechatBotLogin;
use App\Jobs\XbotSendQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WechatBot extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'login_at', 'is_live_at'];

    use HasFactory;
    use SoftDeletes;
    use Expirable; //if ($wechatBot->expired()) {}
    use Metable;

    // use LogsActivity;
    // protected static $logAttributes = ['*'];
    // protected static $logAttributesToIgnore = ['none'];
    // protected static $logOnlyDirty = true;

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function wechatClient(){
        return $this->hasOne(WechatClient::class, 'id', 'wechat_client_id');
    }

    // bot和contact关系 N:N
    protected $touches = ['contacts']; //https://github.com/laravel/framework/issues/31597
    // null = ALL
    public function contacts($type=null): BelongsToMany // @see https://laravel.com/docs/8.x/eloquent-relationships#many-to-many
    {
        // $contact = $bot->contacts->where('userName','gh_xxx')->first()
        // $contact->pivot->remark
        // $contact->pivot->seat_user_id
        // $contact->pivot->config
        $relations = $this->belongsToMany(WechatContact::class, 'wechat_bot_contacts')
            ->withTimestamps()
            ->withPivot(['remark','seat_user_id']);
        if(!is_null($type)){
                $relations =  $relations->wherePivot('type', $type);
        }
        return $relations;
    }

    // WechatBot::find(1)->autoReplies()->create(['keyword'=>'hi','wechat_content_id'=>1]);
    public function autoReplies()
    {
        return $this->hasMany(WechatAutoReply::class)
            ->orderBy('updated_at','desc'); // 最近编辑的，作为第一个匹配来响应
    }

    // '无法找到设备绑定位置，请rootUser设置token:clientAddress绑定'
    public function xbot($clientId=99){
        // 如果数据中存在，则从数据库中去，如果没有，从参数中取，如果还没有，给一个默认值1
        $clientId = $this->client_id??$clientId??-1;
        $wechatClient = WechatClient::where('id', $this->wechat_client_id)->firstOrFail();
        $winClientUri = $wechatClient->xbot;
        return new Xbot($winClientUri, $this->wxid, $clientId);
    }

    public function _send($to, WechatContent $wechatContent){
        $type = WechatContent::TYPES[$wechatContent->type];
        $xbot = $this->xbot();
        $data = $wechatContent->content;
        if($type == 'text' || $type == 'at') {
            // template :nickname :sex @bluesky_still
            $content = $data['content'];
            // :remark 备注或昵称
            // :nickname 好友自己设置的昵称
            // :seat 客服座席名字
            // 第:no号好友
            if(Str::contains($content, [':remark', ':nickname', ':seat'])){
                $contact = WechatBotContact::with('contact', 'seat')
                        ->where('wechat_bot_id', $this->id)
                        ->where('wxid', $to)
                        ->first();
                if(!$contact) return; // 发送给 filehelper, 没有！
                $remark = $contact->remark;
                $nickname = $contact->contact->nickname;
                $seat = $contact->seat->name;
                // $no = $contact->id;

                $content = preg_replace_array('/:remark/', [$remark], $content);
                $content = preg_replace_array('/:nickname/', [$nickname], $content);
                $content = preg_replace_array('/:seat/', [$seat], $content);
                // $content = preg_replace_array('/:no/', [$no], $content);
            }
            if($type == 'text')     $xbot->sendText($to, $content);
            if($type == 'at')       $xbot->sendAtText($to, $content, $data['at']);
        }

        // "C:\\Users\\Public\\Pictures\\$file";
        if($type == 'file')     $xbot->sendFile($to, str_replace("/","\\\\",$data['file']));
        if($type == 'image')    $xbot->sendImage($to, str_replace("/","\\\\",$data['image']));
        if($type == 'contact')     $xbot->sendContactCard($to, $data['content']);
        if($type == 'music')    $xbot->sendMusic($to, $data['url'], $data['title'], "点击🎵收听 {$data['description']}");
        if($type == 'link')     $xbot->sendLink($to, $data['image'], $data['url'],  $data['title'], $data['description']);
    }

    // 批量发送 batch 第一个参数为数组[] wechatContentOrRes
    public function send(array $tos, array | wechatContent $wechatContent){
        if(is_array($wechatContent)) {
            $wechatContent = WechatContent::make([
                'name' => 'tmpSendStructure',
                'type' => array_search($wechatContent['type'], WechatContent::TYPES), //text=>0 这里使用0～9方便数据库存储数字
                'content' => $wechatContent['data'],
            ]);
        }

        // queue sleep(1); // 发送消息过于频繁，可稍后再试。
        $counts = count($tos);
        $count = 0;
        $now = now();
        foreach ($tos as $to) {
            if($counts > 50){
                $delaySeconds = $count++%3600;//1小时内发完5000人
                $delay = $now->addSeconds($delaySeconds);
                XbotSendQueue::dispatch($this, $to, $wechatContent)->delay($delay);
            }else{
                $this->_send($to, $wechatContent);
            }
        }
    }

    public function getResouce($keyword){
        $cacheKey = "resources.{$keyword}";
        if(!($res = Cache::get($cacheKey, false))){
            $response = Http::get(config('xbot.resource_endpoint')."{$keyword}"); //慢
            if($response->ok() && $res = $response->json()) Cache::put($cacheKey, $res, strtotime('tomorrow') - time());
        }
        return $res;
    }

    public function logout(){
        $xbot = $this->xbot();
        $xbot->quit();
        $xbot->loadQR();

        $this->login_at = null;
        $this->is_live_at = null;
        $this->client_id = null;
        $this->save();
    }

    public function login($clientId){
        $this->login_at = now();
        $this->is_live_at = now();
        $this->client_id = $clientId;
        $this->save();
        // 登陆成功，通知前端刷新页面
        WechatBotLogin::dispatch($this->id);
    }

    // 程序崩溃时，login_at 还在，咋办？
    public function isLive(){
        $this->xbot()->getSelfInfo();
        sleep(5); //给callback5秒时间 执行 MT_DATA_OWNER_MSG，更新 is_live_at，然后 refresh，获取最新的 检测时间。
        $lastCheck = $this->is_live_at;
        $this->refresh();
        Log::info(__CLASS__, [__LINE__, $this->wxid, $this->client_id, 'XbotIsLive 2次检测时间', $lastCheck, $this->is_live_at]);

        // Try 3 time? TODO. 第1次没反应时，却在线，怎么办？
        if (optional($this->is_live_at)->diffInMinutes() > 1){ // 如果时间大于1分钟 则代表离线
            // $this->logout();//对此client_id调一次二维码，如果此clientId被别人使用了呢？岂不是把别人下线了？
            Log::error(__CLASS__, [__LINE__, 'XbotIsLive 程序崩溃时,已下线！', $this->wxid, $this->client_id]);
            $this->login_at = null;
            $this->is_live_at = null;
            $this->client_id = null;
            $this->save();
        }
    }

    public function init(){
        $xbot = $this->xbot();
        $xbot->getFriends();
        $xbot->getRooms();
        $xbot->getPublics();
        // @see  XbotCallbackController MT_DATA_OWNER_MSG
        $xbot->getSelfInfo();
    }

    public function syncContacts($contacts, $xbotContactCallbackType){
        $attachs = [];
        foreach ($contacts as $data) {
            $type = WechatContact::CALLBACKTYPES[$xbotContactCallbackType]; //0公众号，1联系人，2群 3群陌生人
            $data['type'] = $type;
            $data['nickname'] = $data['nickname']??$data['wxid'];
            $data['avatar'] = $data['avatar']??'';
            $data['remark'] = $data['remark']??$data['nickname']??$data['wxid'];
            // 联系人 入库
            ($wechatContact = WechatContact::firstWhere('wxid', $data['wxid']))
                ? $wechatContact->update($data) // 更新资料
                : $wechatContact = WechatContact::create($data);

            // Bot联系人 关联
            $wechatBotContact = WechatBotContact::where('wechat_bot_id', $this->id)
                ->where('wechat_contact_id', $wechatContact->id)->first();


            // 如果是群
            if($wechatContact->type == 2){
                $this->syncRoomMemembers($data);
                // 更新群meta, 确保群已经入库
                if(!$wechatBotContact) {
                    $wechatBotContact = WechatBotContact::create([
                        'wechat_bot_id' => $this->id,
                        'wechat_contact_id' => $wechatContact->id,
                        'type' => $type,
                        'wxid' => $wechatContact->wxid,
                        'remark' => $data['remark']??$data['nickname']??$wechatContact->wxid,
                        'seat_user_id' => $this->user_id, //默认坐席为bot管理员
                    ]);
                }
                $wechatBotContact->setMeta('group', Arr::only($data, ['is_manager', 'manager_wxid', 'total_member']));
            }elseif(!$wechatBotContact){
                $attachs[$wechatContact->id] = [
                    'type' => $type,
                    'wxid' => $wechatContact->wxid,
                    'remark' => $data['remark']??$data['nickname']??$wechatContact->wxid,
                    'seat_user_id' => $this->user_id, //默认坐席为bot管理员
                ];
            }
        }

        // @see https://laravel.com/docs/8.x/eloquent-relationships#updating-many-to-many-relationships
        $this->contacts()->syncWithoutDetaching($attachs);
        Log::debug(__CLASS__,[__FUNCTION__, __LINE__, '已同步好友', $this->wxid, $type, count($attachs)]);
    }

    protected function syncRoomMemembers($data)
    {
        // 把群成员 也 写入 wechat_contact 数据库，以供webchat 群回话调用
        // 但要给一个特殊的type:3群陌生人
        $attachs = [];
        foreach ($data['member_list'] as $wxid) {
            $wechatContact = WechatContact::firstWhere('wxid', $wxid);
            if(!$wechatContact){
                $wechatContact = WechatContact::create([
                    'type' => 1, //默认为1 联系人
                    'wxid' => $wxid,
                    'remark' => $wxid,
                    'nickname' => $wxid,
                ]);
            }

            $wechatBotContact = WechatBotContact::where('wxid', $wxid)
                ->where('wechat_bot_id', $this->id)->first();
            if(!$wechatBotContact){ // if已经存在，说明是好友
                $attachs[$wechatContact->id] = [
                    'type' => 3,// 群成员 特殊的type:3群陌生人
                    'wxid' => $wechatContact->wxid,
                    'remark' => $wechatContact->remark??$wechatContact->wxid,
                    'seat_user_id' => $this->user_id, //默认坐席为bot管理员
                ];
            }
        }

        if($counts = count($attachs)){
            // @see https://laravel.com/docs/8.x/eloquent-relationships#updating-many-to-many-relationships
            $this->contacts()->syncWithoutDetaching($attachs);
            Log::debug(__CLASS__,[__FUNCTION__, __LINE__, '群成员已同步', $this->wxid, $data['wxid'], $data['nickname'], $counts]);
        }
    }

}
