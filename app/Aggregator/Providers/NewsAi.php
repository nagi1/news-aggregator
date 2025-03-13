<?php

namespace App\Aggregator\Providers;

use App\Aggregator\AbstractNewsProvider;
use App\Enums\NewsProviderEnum;
use App\Support\ArticleDto;
use App\Support\NewsProviderOptions;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;

class NewsAi extends AbstractNewsProvider
{
    public static $totalResultsKey = 'articles.totalResults';

    public static $articlesKey = 'articles.results';

    protected function normalizeArticle(array $article): ArticleDto
    {
        return new ArticleDto(
            slug: str($article['title'])->slug('-')->toString(),
            title: $article['title'],
            description: str($article['body'])->words(10, '...')->toString(),
            url: $article['url'],
            publishedAt: Carbon::parse($article['dateTimePub']),
            content: $article['body'],
            source: data_get($article, 'source.title'),
            author: data_get($article, 'author.0.name'),
            image: $article['image'],
            apiProvider: NewsProviderEnum::NEWS_AI,
        );
    }

    /**
     * Fetch a page of articles from NewsAI API.
     *
     * @see https://newsapi.ai/documentation?tab=searchArticles
     *
     * @param  NewsProviderOptions  $options  The search criteria
     * @param  int  $page  The page number to fetch
     * @return Response|null Response object or null on failure
     */
    protected function fetchPage(NewsProviderOptions $options, int $page): ?Response
    {
        $params = [
            'keyword' => $options->getKeywords(),
            'keywordOper' => 'or',
            'keywordLoc' => 'title,body',
            'dateStart' => $options->getFromDate()->format('Y-m-d'),
            'dateEnd' => $options->getToDate()->format('Y-m-d'),
            'lang' => 'eng',
            'isDuplicateFilter' => 'skipDuplicates',
            'articlesCount' => $options->getLimit(),
            'articlesPage' => $page,
            'dataType' => ['news'],
            'articlesSortByAsc' => false,
            'resultType' => 'articles',
            'action' => 'getArticles',
            'includeArticleCategories' => true,
            'apiKey' => $this->config['api_key'],
        ];

        // Make the API request with timeout and retry handling
        $response = $this->http()
            ->post('article/getArticles', $params);

        // Validate the response
        if ($response->failed()) {
            $statusCode = $response->status();
            $errorMessage = $response->json('error.message') ?? 'Unknown error';

            logger()->error("Failed to fetch news from NewsAI API (page {$page})", [
                'statusCode' => $statusCode,
                'error' => $errorMessage,
                'response' => $response->json(),
            ]);

            return null;
        }

        return $response;

    }
}
