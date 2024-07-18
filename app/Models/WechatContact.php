<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WechatContact extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $appends = ['isRoom'];
    use SoftDeletes;

    // 0公众号，1联系人，2群
    const TYPES = [
        'public'=>0, // 0
        'friend'=>1, // 1
        'group'=>2, // 2 group
        'stranger'=>3, // 3 group stranger
    ];
    const TYPES_NAME = [
        '公众号', // 0
        '好友', // 1
        '群聊', // 2 group
        '群友', // 3 group stranger
    ];
    const TYPE_PUBLIC = 0;
    const TYPE_FRIEND = 1;
    const TYPE_GROUP = 2;
    const TYPE_STRANGER = 3;
    // 0公众号，1联系人，2群
    const CALLBACKTYPES = [
        'MT_DATA_PUBLICS_MSG'=>0, // 0
        'MT_DATA_FRIENDS_MSG'=>1, // 1
        'MT_DATA_CHATROOMS_MSG'=>2, // 2 group
    ];

    const SEX = [
        '未知',
        '男',
        '女'
    ];
    public function getIsRoomAttribute(){
        return Str::endsWith($this->wxid, '@chatroom')?true:false;
    }
    public function conversations()
    {
        // 为什么 Conversion 不显示所有的？
            // 因为太多，页面太长、太卡！

        //Conversion显示最近xx天的聊天记录： updated_at > now() — 30/90 
            // 🏅️：群30天，个人60天  //默认
            // 🏅️：群90天，个人180天
            // 🏅️：群180天，个人360天
            
        // TODO  Custom level 
        $history = now()->subDays($this->type==2?30:60);
        return $this->hasMany(WechatMessage::class, 'conversation')->where('updated_at', '>', $history);
    }
    
    public function getAvatarAttribute($value){
        return str_replace('http://', 'https://', $value);
    }
}
