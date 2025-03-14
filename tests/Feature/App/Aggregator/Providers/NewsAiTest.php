<?php

use App\Aggregator\Providers\NewsAi;
use App\Support\NewsProviderOptions;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use App\Support\ArticleDto;
use Carbon\Carbon;

beforeEach(function () {
    $this->config = [
        'base_url' => 'https://newsai.example.com/api',
        'api_key'  => 'test_api_key',
    ];
});

it('fetches articles successfully and normalizes them', function () {
    Http::fake(function ($request) {
        // Ensure the request is made to the correct endpoint.
        if ($request->url() === 'https://newsai.example.com/api/article/getArticles') {
            return Http::response([
                'articles' => [
                    'totalResults' => 1,
                    'results' => [
                        [
                            'title' => 'Test Article',
                            'body' => 'This is a test body content for the article and it has more than ten words for the purpose of testing.',
                            'url' => 'http://example.com/test-article',
                            'dateTimePub' => Carbon::now()->subDay()->toIso8601String(),
                            'image' => 'http://example.com/image.jpg',
                            'source' => ['title' => 'Test Source'],
                            'author' => [['name' => 'Test Author']],
                        ],
                    ],
                ],
            ], 200);
        }
        return Http::response([], 404);
    });

    $newsAi = new NewsAi($this->config);

    // Create an options instance (others properties are managed by NewsProviderOptions)
    $options = new NewsProviderOptions(now()->subDay());

    /** @var LazyCollection $articles */
    $articles = $newsAi->fetchLatestNewsCursor($options)->all();

    expect($articles)->toBeArray();
    expect(count($articles))->toBeGreaterThan(0);

    foreach ($articles as $article) {
        expect($article)->toBeInstanceOf(ArticleDto::class);
        expect($article->title)->toBe('Test Article');
    }
});
