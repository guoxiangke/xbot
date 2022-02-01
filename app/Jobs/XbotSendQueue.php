<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class XbotSendQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $wechatBot;
    public $to;
    public $wechatContent;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($wechatBot, $to, $wechatContent)
    {
        $this->wechatBot = $wechatBot; // wxs40F9.tmp
        $this->to = $to;
        $this->wechatContent = $wechatContent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->wechatBot->_send($this->to, $this->wechatContent);
    }
}
