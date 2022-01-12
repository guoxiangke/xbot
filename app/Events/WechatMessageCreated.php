<?php

namespace App\Events;

use App\Models\WechatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WechatMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The wechatMessage that created.
     *
     * @var \App\Models\WechatMessage
     */
    protected $wechatMessage;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(WechatMessage $wechatMessage)
    {
        $this->wechatMessage = $wechatMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("xbot.".$this->wechatMessage->wechat_bot_id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return ['id' => $this->wechatMessage->id];
    }
}
