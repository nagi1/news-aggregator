<?php

namespace App\Aggregator\Providers;

use App\Contracts\NewsProviderContract;
use App\Support\NewsProviderOptions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;

class NewsAi implements NewsProviderContract
{
    public function __construct(protected array $config) {}

    /**
     * Fetch the latest news from the provider.
     *
     * @see https://newsapi.ai/documentation?tab=searchArticles
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
        "query": {
        "$query": {
            "categoryUri": "dmoz/Computers/Artificial_Intelligence/Agents"
        },
        "$filter": {
            "forceMaxDataTimeWindow": "31"
        }
        */
        $categories = collect($options->getCategories())->map(function ($category) {
            return [
                'categoryUri' => $category,
            ];
        });

        $response = $this->http()->get('article/getArticles', [
            'query' => [
                '$query' => [
                    '$or' => collect($options->getKeywords())->map(function ($keyword) {
                        return [
                            'keyword' => $keyword,
                            'keywordLoc' => 'title',
                        ];
                    })
                        ->merge($categories)
                        ->toArray(),

                ],
            ],
            'dateStart' => $options->getFromDate()->format('Y-m-d'),
            'dateEnd' => $options->getToDate()->format('Y-m-d'),
            'lang' => 'eng',
            'isDuplicateFilter' => 'skipDuplicates',
            'articlesCount' => 100,
            'articlesPage' => $page,
            'dataType' => ['news'],
            'articlesSortByAsc' => false,
            'resultType' => 'articles',
            'action' => 'getArticles',
            'includeArticleCategories' => true,
            'apiKey' => $this->config['api_key'],
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
        return Http::baseUrl($this->config['base_url'])
            ->asJson()
            ->acceptJson();
    }
}
