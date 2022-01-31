<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\WechatBot;
use App\Models\WechatContact;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $xbotSubscriptions = \App\Models\XbotSubscription::with(['wechatContact','user'])->get();
        foreach ($xbotSubscriptions as $xbotSubscription) {
            $botId = WechatBot::firstWhere('user_id', $xbotSubscription->user_id)->id;
            $to = WechatContact::find($xbotSubscription->wechat_contact_id)->wxid;
            $keyword = $xbotSubscription->keyword;
            $schedule->command("trigger:xbot $botId $to $keyword")->cron($xbotSubscription->cron);
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
