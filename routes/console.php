<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ランキングは毎月1日1:00に集計し、同日7:00からフロントへ公開する（公開判定は TopPageService 側）
Schedule::command('rankings:aggregate')->monthlyOn(1, '01:00');
