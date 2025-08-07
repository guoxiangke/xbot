<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\XbotSubscription;
use Illuminate\Support\Str;
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
        $schedule->command('xbot:islive')->hourly();
        $xbotSubscriptions = XbotSubscription::with(['wechatBotContact'])->get();
        foreach ($xbotSubscriptions as $xbotSubscription) {
            if(is_null($xbotSubscription->wechatBotContact)){
                $xbotSubscription->delete();
                continue;
            }
            // 不是群的不订阅！真爱
            $to = $xbotSubscription->wechatBotContact->wxid;
            if($xbotSubscription->wechat_bot_id==13 && !Str::endsWith($to, '@chatroom')){
                continue;
            }
            // 友4
            if($xbotSubscription->wechat_bot_id==1 && !Str::endsWith($to, '@chatroom')){
                continue;
            }
            $schedule->command("trigger:xbot $xbotSubscription->id")->cron($xbotSubscription->cron);
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
