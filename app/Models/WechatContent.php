<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatContent extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'created_at', 'updated_at'];
    // 可以主动发送的消息类型 @see App\Services\WechatBot->sendApp()
    const TYPES_CN = ['文本','@群','图片','文件','链接','音乐'];
    const TYPES = [
        "text",
        "at",
        "image",
        "file",
        "link",
        "music",
    ];

    protected $casts = [ 'content' => 'array'];
    
    const TYPE_TEMPLATE = 0;
    const TYPE_TEXT = 1;
    const TYPE_IMAGE = 2;
    const TYPE_FILE = 3;
    const TYPE_LINK = 4;

    public function getCnTypeAttribute()
    {
        return self::TYPES_CN[$this->type];
    }

    public function getContentASText()
    {
        $content = '';
        $typeName = self::TYPES[$this->type];
        switch ($typeName) {
            case 'template':
            case 'text':
                $content =  $this->content['content'];
                break;

            case 'image':
                $content =  $this->content['image'];
                break;

            case 'file': //mp3 mp4
                $content =  $this->content['file'];
                break;

            case 'card':
                $content =  $this->content['nameCardId'];
                break;

            case 'link':
                $content =  $this->content['url'];
                break;

            default:
                # code...
                break;
        }
        return $content;
    }
}
