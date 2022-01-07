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
    ];
    const TYPES_TEXT = [
        'text',             //0
        'emoji',            //1
        'voice',            //2
        'image',            //3
        'file',             //4
        'video',             //5
    ];

    protected $appends = ['isSentByBot', 'contents'];
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
        switch ($this->type) {
            case 2: //voice
            case 3: //image
            case 4: //file
            case 5: //video
                $content = config('xbot.upyun') . $value;
                break;
            
            // case 0: //text
            // case 1: //emoji
            default:
                $content = $value;
                break;
        }
        return $content;
    }

    public function to(){
        return $this->hasOne(WechatBotContact::class, 'id', 'conversation');
    }

    public function wechatBot(){
        return $this->hasOne(WechatBot::class, 'id', 'wechat_bot_id');
    }

    public function seatUser(){
        return $this->hasOne(User::class, 'id', 'seat_user_id');
    }
}
