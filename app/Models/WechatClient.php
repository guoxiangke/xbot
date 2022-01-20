<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\Xbot;

class WechatClient extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // 打开1个微信客户端
    public function new()
    {
        $winClientUri = $this->xbot;
        $xbot = new Xbot($winClientUri);
        $xbot->newClient();
    }

    // 关闭微信客户端
    public function close($clientId)
    {
        $winClientUri = $this->xbot;
        $xbot = new Xbot($winClientUri);
        $xbot->closeClient($clientId);
    }
}
