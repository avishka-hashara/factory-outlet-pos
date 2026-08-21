<?php

use App\Models\SmsOutbox;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Safety net: flip any SMS still stuck on "pending" past its timeout to
// "failed" with a clear reason, even for bills nobody is actively polling.
// Requires the scheduler cron (`php artisan schedule:run`) to be running.
Schedule::call(function () {
    SmsOutbox::where('status', SmsOutbox::STATUS_PENDING)
        ->get()
        ->each(fn (SmsOutbox $row) => $row->resolveIfTimedOut());
})->everyMinute()->name('sms-outbox-timeout-sweep')->withoutOverlapping();
