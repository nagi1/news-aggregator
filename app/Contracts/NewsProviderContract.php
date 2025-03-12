<?php

namespace App\Contracts;

use App\Support\NewsProviderOptions;

interface NewsProviderContract
{
    /**
     * Fetch the latest news from the provider.
     *
     * @param  NewsProviderOptions  $options  Options for fetching news.
     */
    public function fetchLatestNewsCursor(NewsProviderOptions $options): \Illuminate\Support\LazyCollection;
}
