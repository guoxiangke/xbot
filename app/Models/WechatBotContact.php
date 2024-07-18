<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Metable\Metable;
use Spatie\Tags\HasTags;

class WechatBotContact extends Model
{
    use SoftDeletes;
    use HasFactory;
    use Metable;
    use HasTags;
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    const TYPES = WechatContact::TYPES;
    const TYPES_NAME = WechatContact::TYPES_NAME;
    const DEFAULT_AVATAR = 'https://mmbiz.qpic.cn/mmbiz/icTdbqWNOwNRna42FI242Lcia07jQodd2FJGIYQfG0LAJGFxM4FbnQP6yfMxBgJ0F3YRqJCJ1aPAK2dQagdusBZg/0?wx_fmt=png';
    // 1:1
    public function contact(){
        return $this->hasOne(WechatContact::class, 'id', 'wechat_contact_id');
    }

    public function seat(){
        return $this->belongsTo(User::class, 'seat_user_id');
    }

    public function wechatBot(){
        return $this->belongsTo(WechatBot::class, 'wechat_bot_id');
    }

    // public function messages(){
    //     return $this->hasMany(WechatMessage::class, 'conversation', 'wechat_contact_id');
    // }
}
