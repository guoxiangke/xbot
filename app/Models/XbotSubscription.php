<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class XbotSubscription extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at']; // If you choose to unguard your model, you should take special care to always hand-craft the arrays passed to Eloquent's fill, create, and update methods: https://laravel.com/docs/8.x/eloquent#mass-assignment-json-columns

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wechatContact()
    {
        return $this->belongsTo(WechatContact::class);
    }
}
