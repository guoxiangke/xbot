<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatContent extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'created_at', 'updated_at'];
    // 可以主动发送的消息类型 @see App\Services\WechatBot->sendApp()
    const TYPES_CN = ['文本','@群','图片','文件','链接','音频'];
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
    const TYPE_VIDEO = 3; 

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
            case 'video':
                $content =  $this->content['path'];
                break;

            case 'image':
                $content =  $this->content['image'];
                break;
            case 'file':
                // $content =  $this->content['content'];
                break;

            case 'card':
                $content =  $this->content['nameCardId'];
                break;

            case 'link':
            case 'music':
                $content =  $this->content['url'];
                break;

            case 'app':
                // $content =  $this->content['content'];
                break;
            default:
                # code...
                break;
        }
        return $content;
    }
}
