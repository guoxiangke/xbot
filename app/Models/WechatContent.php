<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatContent extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'created_at', 'updated_at'];
    // 可以主动发送的消息类型 @see App\Services\WechatBot->sendApp()
    const TYPES_CN = ['文本','@群','图片','文件','链接','音乐','名片','位置',
        '朋友圈视频',
        '朋友圈图片',
        '朋友圈链接',
        '朋友圈音乐',
        '图片链接',
        '朋友圈QQ音乐',
        "消息转发",
    ];
    const TYPES = [
        "text",
        "at",
        "image",
        "file",
        "link",
        "music",
        "contact",
        "location",
        "postVideo",
        "postImages",
        "postLink",
        "postMusic",
        'imageUrl',
        "postQQMusic",
        "forward",
    ];

    protected $casts = [ 'content' => 'array'];
    
    const TYPE_TEMPLATE = 0;
    const TYPE_TEXT = 1;
    const TYPE_IMAGE = 2;
    const TYPE_FILE = 3;
    const TYPE_LINK = 4;
    const TYPE_MUSIC= 5;
    const TYPE_CONTACT= 6;
    const TYPE_LOCATION= 7;

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

            case 'imageUrl':
                $content =  $this->content['url'];
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

            case 'contact':
                $content =  $this->content['wxid'];
                break;

            case 'music':
                $content =  $this->content['title'];
                break;

            case 'location':
                $content =  $this->content['label'] . ':' . $this->content['poiname'];
                break;

            default:
                # code...
                break;
        }
        return $content;
    }
}
