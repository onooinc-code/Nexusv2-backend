<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('ai:poll-health')->everyFiveMinutes();
\Illuminate\Support\Facades\Schedule::command('ai:rotate-keys')->daily();

\Illuminate\Support\Facades\Schedule::call(function () {
    app(\App\Services\TaskSchedulingService::class)->processDueTasks();
})->everyMinute();

\Illuminate\Support\Facades\Schedule::call(function () {
    app(\App\Services\Workflows\WorkflowScheduleService::class)->processScheduledWorkflows();
})->everyMinute();
