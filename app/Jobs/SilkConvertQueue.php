<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SilkConvertQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $file;
    public $wxid;
    public $msgid;
    public $silkDomain;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file, $wxid, $msgid, $silkDomain)
    {
        $this->file = $file; // wxs40F9.tmp
        $this->wxid = $wxid;
        $this->msgid = $msgid;
        $this->silkDomain = $silkDomain;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // silk => mp3
        // $cdn = "https://silk.yongbuzhixi.com{$path}";
        $url = $this->silkDomain . '/' . $this->file;

        $silkDir = "/public/voices/{$this->wxid}/";
        $silkPath ="{$silkDir}{$this->msgid}.mp3";
        Storage::makeDirectory($silkDir);
        Storage::put($silkPath, file_get_contents($url));

        exec("sh /app/converter.sh /var/www/html/storage/app/{$silkPath} mp3");
        Log::debug(__METHOD__, ['语音消息转换完毕', $silkPath]);
    }
}
