<?php

namespace App\Aggregator;

use App\Contracts\NewsProviderContract;
use App\Support\ArticleDto;
use App\Support\NewsProviderOptions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;

abstract class AbstractNewsProvider implements NewsProviderContract
{
    public static $totalResultsKey = 'totalResults';

    public static $articlesKey = 'articles';

    public function __construct(protected array $config) {}

    /**
     * Fetch the latest news from the provider.
     *
     * @return LazyCollection<ArticleDto>
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

                $totalResults = $response->json(static::$totalResultsKey, 0);

                if ($totalResults === 0) {
                    break;
                }

                // Process articles
                $articles = $response->json(static::$articlesKey, []);

                foreach ($articles as $article) {
                    yield $this->normalizeArticle($article);

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

    abstract protected function fetchPage(NewsProviderOptions $options, int $page): ?Response;

    abstract protected function normalizeArticle(array $article): ArticleDto;

    public function http(): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'])
            ->timeout(15)
            ->retry(3, 1000)
            ->asJson()
            ->acceptJson();
    }
}
