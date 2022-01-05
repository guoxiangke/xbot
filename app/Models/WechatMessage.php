<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatMessage extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    const TYPES = [
        'MT_RECV_TEXT_MSG', 
        'MT_RECV_VOICE_MSG',
        'MT_RECV_EMOJI_MSG',
        'MT_RECV_PICTURE_MSG',
        'MT_RECV_FILE_MSG',
    ];
}
