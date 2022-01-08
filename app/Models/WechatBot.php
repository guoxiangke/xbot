<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Plank\Metable\Metable;
// use Spatie\Activitylog\Traits\LogsActivity;
use Mvdnbrk\EloquentExpirable\Expirable;
use App\Services\Xbot;
use App\Models\User;

class WechatBot extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'login_at'];

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

    // bot和contact关系 N:N
    protected $touches = ['contacts']; //https://github.com/laravel/framework/issues/31597
    public function contacts(): BelongsToMany // @see https://laravel.com/docs/8.x/eloquent-relationships#many-to-many
    {
        // $contact = $bot->contacts->where('userName','gh_xxx')->first()
        // $contact->pivot->remark
        // $contact->pivot->seat_user_id
        // $contact->pivot->config
        return $this->belongsToMany(WechatContact::class, 'wechat_bot_contacts')
            ->withTimestamps()
            ->withPivot(['remark','seat_user_id']);
    }


    // '无法找到设备绑定位置，请rootUser设置token:clientAddress绑定'
    public function xbot($clientId=null){
        if(!$clientId) {
            $clientId = $this->client_id;
        }
        $user = User::find(1);
        $tokens = $user->getMeta('xbot.token');
        $clientAddress = $tokens[$this->token];
        return new Xbot($this->wxid, $clientAddress, $clientId);
    }

    private function _send($to, WechatContent $wechatContent){
        $type = WechatContent::TYPES[$wechatContent->type];
        $xbot = $this->xbot();
        $request = $wechatContent->content;
        if($type == 'text')     $xbot->sendText($to, $request['content']);  // TODO template!

        if($type == 'at')       $xbot->sendAtText($to, $request['content'], $request['at']);
        // TODO file&image!
        // if($type == 'file')     $xbot->sendFile($to, $request['url']);
        // if($type == 'image')    $xbot->sendImage($to, $request['url']);
        if($type == 'music')    $xbot->sendMusic($to, $request['url'], $request['title'], $request['desc']);
        if($type == 'link')     $xbot->sendLink($to, $request['image'], $request['url'],  $request['title'], $request['desc']);
    }

    // 批量发送 batch 第一个参数为数组[]
    public function send($tos, WechatContent $wchatContent){
        foreach ($tos as $to) {
            $this->_send($to, $wchatContent);
        }
    }

    public function logout(){
        $xbot = $this->xbot();
        $xbot->quit();
        $xbot->open();
    }
    
    public function init(){
        $xbot = $this->xbot();
        $xbot->getFriends();
        //需要执行2次 getRooms()
        $xbot->getRooms();//第一次初始化数据
        $xbot->getRooms();//第二次attach group meta
        $xbot->getPublics();
        // @see  XbotCallbackController MT_DATA_OWNER_MSG 
        $xbot->getSelfInfo();
    }

    public function syncContacts($contacts, $type){
        $botOwnerId = $this->user_id;
        $attachs = [];
        foreach ($contacts as $data) {
            $data['type'] = WechatContact::CALLBACKTYPES[$type];
            $data['nickname'] = $data['nickname']??$data['wxid'];
            $data['avatar'] = $data['avatar']??'';
            $data['remark'] = $data['remark']??$data['nickname']??$data['wxid'];
            ($contact = WechatContact::firstWhere('wxid', $data['wxid']))
                ? $contact->update($data) // 更新资料
                : $contact = WechatContact::create($data);

            $wechatBotContact = WechatBotContact::where('wechat_bot_id', $this->id)
                ->where('wechat_contact_id', $contact->id)->first();

            // 已经存在的不用更新，防止CRM备注被覆盖
            if($wechatBotContact){
                // 如果是群，更新群meta
                if($contact->type == 2){//2=群
                    $groupData =  Arr::only($data, ['is_manager', 'member_list', 'manager_wxid', 'total_member']);
                    $wechatBotContact->setMeta('group', $groupData);
                    
                    // 把群成员 也 写入 wechat_contact 数据库，以供webchat 群回话调用
                    foreach ($data['member_list'] as $wxid) {
                        $gContact = WechatContact::firstWhere('wxid', $wxid);
                        if(!$gContact){
                            $gContact = WechatContact::create([
                                'wxid' => $wxid,
                                'remark' => $wxid,
                                'nickname9' => $wxid,
                            ]);
                        }
                        $attachs[$gContact->id] = [
                            'wxid' => $gContact->wxid,
                            'remark' => $gContact->remark??$gContact->wxid,
                            'seat_user_id' => $botOwnerId, //默认坐席为bot管理员
                        ];
                    }
                }
                // 已经存在的不用更新，防止CRM备注被覆盖
                continue; 
            }

            ;// @see https://laravel.com/docs/8.x/eloquent-relationships#updating-many-to-many-relationships
            $attachs[$contact->id] = [
                'wxid' => $contact->wxid,
                'remark' => $data['remark']??$data['nickname']??$contact->wxid,
                'seat_user_id' => $botOwnerId, //默认坐席为bot管理员
            ];
        }

        $this->contacts()->syncWithoutDetaching($attachs);
        Log::debug(__METHOD__,['已同步', $this->wxid,  $type, count($contacts), count($attachs)]);
    }

}
