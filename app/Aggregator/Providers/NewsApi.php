<?php

namespace App\Aggregator\Providers;

use App\Contracts\NewsProviderContract;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;

class NewsApi implements NewsProviderContract
{
    public function __construct(protected array $config) {}

    /**
     * Fetch the latest news from the provider.
     *
     * @param  string  $fromDate  Minimum date and time for the oldest article allowed.
     * @param  string  $toDate  Maximum date and time for the newest article allowed.
     * @param  int  $limit  Maximum number of articles to fetch.
     *
     * @see https://newsapi.org/docs/endpoints/everything
     */
    public function fetchLatestNewsCursor(string $fromDate, string $toDate, int $limit = 50): LazyCollection
    {
        return LazyCollection::make(function () use ($fromDate, $toDate, $limit) {
            $currentPage = 1;
            $totalFetched = 0;

            do {
                // Get response for current page
                $response = $this->fetchPage($fromDate, $toDate, $currentPage);

                // Break if request failed or no results
                if (! $response || $response->failed()) {
                    break;
                }

                $totalResults = $response->json('totalResults', 0);

                if ($totalResults === 0) {
                    break;
                }

                // Process articles
                $articles = $response->json('articles', []);

                foreach ($articles as $article) {
                    yield $article;
                    $totalFetched++;

                    // Stop if we've reached the limit
                    if ($totalFetched >= $limit) {
                        return;
                    }
                }

                // Move to next page
                $currentPage++;

                // Stop if we've processed all available articles
                if ($currentPage > ceil($totalResults / 100)) {
                    break;
                }
            } while (true);
        });
    }

    /**
     * Fetch a single page of results from NewsAPI
     *
     * @param  string  $fromDate  The start date for articles
     * @param  string  $toDate  The end date for articles
     * @param  int  $pageSize  Number of articles per page
     * @param  int  $page  Page number to fetch
     */
    protected function fetchPage(string $fromDate, string $toDate, int $page): ?Response
    {
        $response = $this->http()->get('everything', [
            'from' => $fromDate,
            'to' => $toDate,
            'language' => 'en',
            'pageSize' => 100,
            'page' => $page,
        ]);

        if ($response->failed()) {
            logger()->error("Failed to fetch news from News API (page {$page})", [
                'response' => $response->json(),
            ]);

            return null;
        }

        return $response;
    }

    public function http(): PendingRequest
    {
        return Http::withHeaders([
            'X-Api-Key' => $this->config['api_key'],
        ])->baseUrl($this->config['base_url']);
    }
}
