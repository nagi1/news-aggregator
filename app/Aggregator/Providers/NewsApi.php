<?php

namespace App\Aggregator\Providers;

use App\Contracts\NewsProviderContract;
use App\Support\NewsProviderOptions;
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
     * @see https://newsapi.org/docs/endpoints/everything
     */
    public function fetchLatestNewsCursor(NewsProviderOptions $options): LazyCollection
    {
        return LazyCollection::make(function () use (&$options) {
            $currentPage = 1;
            $totalFetched = 0;

            do {
                // Get response for current page
                $response = $this->fetchPage($options, $currentPage);

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
                    if ($totalFetched >= $options->getLimit()) {
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

    protected function fetchPage(NewsProviderOptions $options, int $page): ?Response
    {
        /*
        q
            Keywords or phrases to search for in the article title and body.

            Advanced search is supported here:

            Surround phrases with quotes (") for exact match.
            Prepend words or phrases that must appear with a + symbol. Eg: +bitcoin
            Prepend words that must not appear with a - symbol. Eg: -bitcoin
            Alternatively you can use the AND / OR / NOT keywords, and optionally group these with parenthesis. Eg: crypto AND (ethereum OR litecoin) NOT bitcoin.
            The complete value for q must be URL-encoded. Max length: 500 chars.
        */
        $response = $this->http()->get('everything', [
            'q' => empty($options->getKeywords()) ? null : implode(' OR ', $options->getKeywords()),
            'from' => $options->getFromDate()->toIso8601String(),
            'to' => $options->getToDate()->toIso8601String(),
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
        ])
            ->baseUrl($this->config['base_url'])
            ->asJson()
            ->acceptJson();
    }
}
