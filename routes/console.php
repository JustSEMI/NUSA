<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Heartbeat AI setiap 5 menit
Schedule::call(function () {
    $service = new \App\Services\AIHeartbeatService();
    $service->checkAllModels();
})->everyFiveMinutes()->name('ai-heartbeat');
