<?php

use App\Enums\NewsProviderEnum;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    foreach (NewsProviderEnum::cases() as $provider) {
        Artisan::call('fetch:news', ['provider' => $provider->value]);
    }
})->everyFifteenMinutes();
