<?php

use Illuminate\Support\Facades\Schedule;

if ($time = config('tabungan.backup_schedule')) {
    Schedule::command('tabungan:backup --prune')->dailyAt($time)->withoutOverlapping();
}
