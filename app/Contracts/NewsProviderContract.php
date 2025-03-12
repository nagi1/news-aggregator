<?php

namespace App\Contracts;

interface NewsProviderContract
{
    /**
     * Fetch the latest news from the provider.
     *
     * @param  string  $fromDate  Minimum date and time for the oldest article allowed.
     * @param  string  $toDate  Maximum date and time for the newest article allowed.
     * @param  int  $limit  Maximum number of articles to fetch.
     */
    public function fetchLatestNewsCursor(string $fromDate, string $toDate, int $limit = 50): \Illuminate\Support\LazyCollection;
}
