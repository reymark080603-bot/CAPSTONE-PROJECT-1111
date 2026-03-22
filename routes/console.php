<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic book returns to run daily at midnight
Schedule::command('books:auto-return')
    ->daily()
    ->at('00:01') // Run at 12:01 AM daily
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('Auto-return books command completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Auto-return books command failed');
    });
