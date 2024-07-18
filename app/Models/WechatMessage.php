<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatMessage extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    //  TYPES => TYPES_TEXT 一一对应
    const TYPES = [
        'MT_RECV_TEXT_MSG', 
        'MT_RECV_EMOJI_MSG',
        'MT_RECV_VOICE_MSG',
        'MT_RECV_PICTURE_MSG',
        'MT_RECV_FILE_MSG',
        'MT_RECV_VIDEO_MSG',
        'MT_RECV_WCPAY_MSG',
        'MT_RECV_LOCATION_MSG',
        'MT_RECV_OTHER_APP_MSG',
        'MT_TRANS_VOICE_MSG',
    ];
    const TYPES_TEXT = [
        'text',             //0
        'emoji',            //1
        'voice',            //2
        'image',            //3
        'file',             //4
        'video',             //5
        'wcpay',             //6
        'location',          //7
        'appLink',           //8 "wx_sub_type":4,"wx_type":49 <type>4</type>
        'voice_text', //9
    ];

    protected $appends = ['isSentByBot'];
    public function getIsSentByBotAttribute()
    {
        return $this->from?false:true;
    }


    public function getContentAttribute($value)
    {
        // ✅  文件消息
        // 监控上传文件夹3 C:\Users\Administrator\Documents\WeChat Files\  =》 /xbot/file/
        // ✅ 收到语音消息，即刻调用转文字
        // 监控上传文件夹2 C:\Users\Administrator\AppData\Local\Temp\ =》 /xbot/voice/
        // ✅ 收到图片
        // 监控上传文件夹1 C:\Users\Public\Pictures\ =》 /xbot/image/
        $content = $value;
        switch ($this->type) {
            // case 2: //voice
                // $content = config('xbot.voiceDomain') . $value;
                // break;
            case 3: //image
            case 4: //file
            case 5: //video
                $content = $this->wechatBot->wechatClient->file . $value;
                break;
            
            // case 0: //text
            // case 1: //emoji
            default:
                $content = $value;
                break;
        }
        return $content;
    }

    // 可以发到群
    public function to(){
        return $this->hasOne(WechatBotContact::class, 'id', 'conversation');
    }

    // 如果是群，需要by
    public function by(){
        return $this->hasOne(WechatBotContact::class, 'id', 'from');
    }

    public function wechatBot(){
        return $this->hasOne(WechatBot::class, 'id', 'wechat_bot_id');
    }

    public function seatUser(){
        return $this->hasOne(User::class, 'id', 'seat_user_id');
    }
}
