<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use App\Models\WechatMessage;
use App\Observers\WechatMessageObserver;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Carbon::setLocale('zh');
        WechatMessage::observe(WechatMessageObserver::class);
        if ($this->app->environment() !== 'local') {
            URL::forceScheme('https');
        }
    }
}
