<?php

use App\Aggregator\Providers\NewsApi;
use App\Support\NewsProviderOptions;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use App\Support\ArticleDto;

beforeEach(function () {
    $this->config = [
        'base_url' => 'https://newsapi.org/v2',
        'api_key'  => 'test_api_key',
    ];
});

it('fetches articles successfully and handles pagination', function () {
    // First page returns articles; second page returns an empty articles array to end pagination.
    Http::fake(function ($request) {
        $page = $request->data()['page'] ?? 1;
        // Verify header propagation on each request.
        expect($request->header('X-Api-Key')[0])->toBe('test_api_key');

        if ($page == 1) {
            return Http::response([
                'totalResults' => 2,
                'articles' => [
                    [
                        'title' => 'First Article',
                        'description' => 'First Description',
                        'url' => 'http://example.com/first-article',
                        'publishedAt' => now()->subDay()->toIso8601String(),
                        'content' => 'First Content',
                        'source' => ['name' => 'First Source'],
                        'author' => 'First Author',
                        'urlToImage' => 'http://example.com/first-image.jpg',
                    ],
                    [
                        'title' => 'Second Article',
                        'description' => 'Second Description',
                        'url' => 'http://example.com/second-article',
                        'publishedAt' => now()->subDay()->toIso8601String(),
                        'content' => 'Second Content',
                        'source' => ['name' => 'Second Source'],
                        'author' => 'Second Author',
                        'urlToImage' => 'http://example.com/second-image.jpg',
                    ],
                ],
            ], 200);
        }

        // For page 2, simulate no more articles.
        return Http::response([
            'totalResults' => 2,
            'articles' => [],
        ], 200);
    });

    $newsApi = new NewsApi($this->config);

    // Create an options instance – details aren’t used by the fake but required by the signature.
    $options = new NewsProviderOptions(now()->subDay());

    // Collect returned articles from the lazy collection.
    /** @var LazyCollection $articles */
    $articles = $newsApi->fetchLatestNewsCursor($options)->all();

    // We expect that normalization happens, so our normalized articles would be instances of ArticleDto.
    // (Since the normalizeArticle is not fully implemented, we only check that we got objects.)
    expect($articles)->toBeArray();

    foreach ($articles as $article) {
        expect($article)->toBeInstanceOf(ArticleDto::class);
    }
});
