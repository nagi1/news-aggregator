<?php

namespace App\Aggregator;

use App\Aggregator\Providers\NewsApi;
use App\Contracts\NewsProviderContract;
use App\Enums\NewsProviderEnum;
use Illuminate\Support\Facades\Config;

class NewsProviderFactory
{
    public static function make(NewsProviderEnum $provider): NewsProviderContract
    {
        $config = Config::get('services.news.'.$provider->value);

        return match ($provider->value) {
            $provider->value => new NewsApi($config),
            default => throw new \InvalidArgumentException('Invalid news provider'),
        };
    }
}
