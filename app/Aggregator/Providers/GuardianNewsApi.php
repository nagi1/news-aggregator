<?php

namespace App\Aggregator\Providers;

use App\Aggregator\AbstractNewsProvider;
use App\Enums\NewsProviderEnum;
use App\Support\ArticleDto;
use App\Support\NewsProviderOptions;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;

class GuardianNewsApi extends AbstractNewsProvider
{
    public static $totalResultsKey = 'response.total';

    public static $articlesKey = 'response.results';

    /**
     * Fetch the latest news from the next page.
     *
     * @see https://open-platform.theguardian.com/documentation/search
     */
    protected function fetchPage(NewsProviderOptions $options, int $page): ?Response
    {
        /*
        q
            Request content containing this free text. Supports AND, OR and NOT operators, and exact phrase queries using double quotes.

            e.g. sausages, "pork sausages", sausages AND (mash OR chips), sausages AND NOT (saveloy OR battered)
        */
        $searchTerms = [
            ...$options->getKeywords(),
            ...$options->getCategories(),
            ...$options->getAuthors(),
            ...$options->getSources(),
        ];

        $response = $this->http()->get('search', [
            'q' => empty($searchTerms) ? null : implode(' OR ', $searchTerms),
            'from-date' => $options->getFromDate()->toDateString(),
            'to-date' => $options->getToDate()->toDateString(),
            'lang' => 'en',
            'page-size' => $options->getLimit(),
            'page' => $page,
            'api-key' => $this->config['api_key'],
            'show-fields' => 'body,thumbnail',
            'show-elements' => 'image',
            'show-section' => 'true',
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
            slug: $article['id'],
            title: $article['webTitle'],
            description: $article['webTitle'],
            url: $article['webUrl'],
            category: data_get($article, 'section.webTitle'),
            publishedAt: Carbon::parse($article['webPublicationDate']),
            content: data_get($article, 'fields.body'),
            source: null,
            author: null,
            image: data_get($article, 'fields.thumbnail'),
            apiProvider: NewsProviderEnum::GUARDIAN,
        );
    }
}
