<?php

namespace App\Aggregator;

use App\Aggregator\Providers\GuardianNewsApi;
use App\Aggregator\Providers\NewsAi;
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
            NewsProviderEnum::NEWS_API->value => app(NewsApi::class, ['config' => $config]),
            NewsProviderEnum::NEWS_AI->value => app(NewsAi::class, ['config' => $config]),
            NewsProviderEnum::GUARDIAN->value => app(GuardianNewsApi::class, ['config' => $config]),
            default => throw new \InvalidArgumentException('Invalid news provider'),
        };
    }
}
