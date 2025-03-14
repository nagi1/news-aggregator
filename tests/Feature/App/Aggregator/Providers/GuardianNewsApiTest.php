<?php

use App\Aggregator\Providers\GuardianNewsApi;
use App\Support\NewsProviderOptions;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use App\Support\ArticleDto;
use Carbon\Carbon;

beforeEach(function () {
    $this->config = [
        // The base_url is not explicitly required by the provider since it uses http()->get('search', ...),
        // but you can include it if your http() implementation relies on it.
        'api_key' => 'test_api_key',
        'base_url' => 'https://content.guardianapis.com',
    ];
});

it('fetches articles successfully and normalizes them', function () {
    Http::fake(function ($request) {
        // Verify that the request is made to the 'search' endpoint.
        expect(str_contains($request->url(), 'search'))->toBeTrue();
        // Check that the API key is passed correctly.
        expect($request->data()['api-key'] ?? null)->toBe('test_api_key');

        return Http::response([
            'response' => [
                'total' => 1,
                'results' => [
                    [
                        'id' => 'guardian-test-article',
                        'webTitle' => 'Test Guardian Article',
                        'webUrl' => 'http://example.com/test-guardian-article',
                        'webPublicationDate' => Carbon::now()->subDay()->toIso8601String(),
                        'section' => [
                            'webTitle' => 'News',
                        ],
                        'fields' => [
                            'body' => 'This is body text for guardian article.',
                            'thumbnail' => 'http://example.com/thumbnail.jpg',
                        ],
                    ],
                ],
            ],
        ], 200);
    });

    $guardianApi = new GuardianNewsApi($this->config);
    $options = new NewsProviderOptions(now()->subDay());

    /** @var LazyCollection $articles */
    $articles = $guardianApi->fetchLatestNewsCursor($options)->all();

    expect($articles)->toBeArray();
    expect(count($articles))->toBeGreaterThan(0);

    foreach ($articles as $article) {
        expect($article)->toBeInstanceOf(ArticleDto::class);
        // Validate normalized fields.
        expect($article->slug)->toBe('guardian-test-article');
        expect($article->title)->toBe('Test Guardian Article');
        expect($article->description)->toBe('Test Guardian Article');
        expect($article->url)->toBe('http://example.com/test-guardian-article');
        expect($article->category)->toBe('News');
        expect($article->content)->toBe('This is body text for guardian article.');
        expect($article->image)->toBe('http://example.com/thumbnail.jpg');
    }
});
