<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
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

    public function send($to, WechatContent $wchatContent){
        $type = WechatContent::TYPES[$wchatContent->type]; //0=>text
        $xbot = $this->xbot();
        $request = $wchatContent->content; //json->array
        if($type == 'text')     $xbot->sendText($to, $request['content']);  // TODO template!

        if($type == 'at')       $xbot->sendAtText($to, $request['content'], $request['at']);
        // TODO file&image!
        // if($type == 'file')     $xbot->sendFile($to, $request['url']);
        // if($type == 'image')    $xbot->sendImage($to, $request['url']);
        if($type == 'music')    $xbot->sendMusic($to, $request['url'], $request['title'], $request['desc']);
        if($type == 'link')     $xbot->sendLink($to, $request['url'],  $request['image'], $request['title'], $request['desc']);
    }

    // 批量发送
    public function sendTo($tos, WechatContent $wchatContent){
        foreach ($tos as $to) {
            $this->send($to, $wchatContent);
        }
    }
}
