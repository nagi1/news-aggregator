<?php

namespace App\Aggregator\Providers;

use App\Aggregator\AbstractNewsProvider;
use App\Enums\NewsProviderEnum;
use App\Support\ArticleDto;
use App\Support\NewsProviderOptions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;

class NewsApi extends AbstractNewsProvider
{
    /**
     * Fetch the latest news from the next page.
     *
     * @see https://newsapi.org/docs/endpoints/everything
     */
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
        $searchTerms = [
            ...$options->getKeywords(),
            ...$options->getCategories(),
            ...$options->getAuthors(),
            ...$options->getSources(),
        ];

        $response = $this->http()->get('everything', [
            'q' => empty($searchTerms) ? null : implode(' OR ', $searchTerms),
            'from' => $options->getFromDate()->toIso8601String(),
            'to' => $options->getToDate()->toIso8601String(),
            'language' => 'en',
            'pageSize' => $options->getLimit(),
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

    protected function normalizeArticle(array $article): ArticleDto
    {
        return new ArticleDto(
            slug: str($article['title'])->slug('-')->toString(),
            title: $article['title'],
            description: $article['description'],
            url: $article['url'],
            publishedAt: Carbon::parse($article['publishedAt']),
            content: $article['content'],
            source: data_get($article, 'source.name'),
            author: $article['author'],
            image: data_get($article, 'urlToImage'),
            apiProvider: NewsProviderEnum::NEWS_API,
        );
    }

    public function http(): PendingRequest
    {
        return parent::http()->withHeaders([
            'X-Api-Key' => $this->config['api_key'],
        ]);
    }
}
