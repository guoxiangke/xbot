<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatContent extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'created_at', 'updated_at'];
    // 可以主动发送的消息类型 @see App\Services\WechatBot->sendApp()
    const TYPES_CN = ['文本','群艾特','图片','文件','链接','音频'];
    const TYPES = [
        "text",
        "at",
        "image",
        "file",
        "link",
        "music",
    ];

    protected $casts = [ 'content' => 'array'];

}
