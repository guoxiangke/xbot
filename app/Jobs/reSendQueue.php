<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\XbotSubscription;
use Illuminate\Support\Facades\Artisan;

class reSendQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public XbotSubscription $xbotSubscription;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(XbotSubscription $xbotSubscription)
    {
        $this->xbotSubscription = $xbotSubscription;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::call("trigger:xbot " . $this->xbotSubscription->id);
    }
}
