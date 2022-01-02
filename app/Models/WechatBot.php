<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Metable\Metable;
// use Spatie\Activitylog\Traits\LogsActivity;
use Mvdnbrk\EloquentExpirable\Expirable;
use App\Services\Xbot;
use App\Models\User;

class WechatBot extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'login_at'];

    use HasFactory;
    use SoftDeletes;
    use Expirable; //if ($wechatBot->expired()) {}
    use Metable;

    // use LogsActivity;
    // protected static $logAttributes = ['*'];
    // protected static $logAttributesToIgnore = ['none'];
    // protected static $logOnlyDirty = true;

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function xbot($clientId=null){
        if(!$clientId) {
            $clientId = $this->client_id;
        }
        $user = User::find(1);
        $tokens = $user->getMeta('xbot.token');
        $clientAddress = $tokens[$this->token];
        return new Xbot($this->wxid, $clientAddress, $clientId);
    }

}
