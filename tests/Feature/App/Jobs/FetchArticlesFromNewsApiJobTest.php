<?php

use App\Aggregator\Providers\NewsApi;
use App\Enums\NewsProviderEnum;
use App\Jobs\FetchArticlesFromNewsApiJob;
use App\Models\Article;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Set fixed date for testing
    Carbon::setTestNow('2025-03-12 12:00:00');
});

test('job fetches and stores news articles', function () {
    // Set up HTTP fake for the NewsAPI endpoint
    Http::fake([
        '*' => Http::response([
            'status' => 'ok',
            'totalResults' => 3,
            'articles' => [
                [
                    'title' => 'Test Article 1',
                    'description' => 'Description for test article 1',
                    'url' => 'https://example.com/article-1',
                    'publishedAt' => now()->subHours(1)->toIso8601String(),
                    'content' => 'Content for test article 1',
                    'source' => ['name' => 'Test Source'],
                    'author' => 'Test Author',
                    'urlToImage' => 'https://example.com/image-1.jpg',
                ],
                [
                    'title' => 'Test Article 2',
                    'description' => 'Description for test article 2',
                    'url' => 'https://example.com/article-2',
                    'publishedAt' => now()->subHours(2)->toIso8601String(),
                    'content' => 'Content for test article 2',
                    'source' => ['name' => 'Test Source'],
                    'author' => 'Test Author',
                    'urlToImage' => 'https://example.com/image-2.jpg',
                ],
                [
                    'title' => 'Test Article 3',
                    'description' => 'Description for test article 3',
                    'url' => 'https://example.com/article-3',
                    'publishedAt' => now()->subHours(3)->toIso8601String(),
                    'content' => 'Content for test article 3',
                    'source' => ['name' => 'Test Source'],
                    'author' => 'Test Author',
                    'urlToImage' => 'https://example.com/image-3.jpg',
                ],
            ],
        ], 200),
    ]);

    // Execute the job
    (new FetchArticlesFromNewsApiJob)->handle();

    // Verify articles were saved
    expect(Article::count())->toBe(3);

    // Verify an article's data is correctly saved
    $article = Article::where('title', 'Test Article 1')->first();
    expect($article)->not->toBeNull()
        ->and($article->slug)->toBe('test-article-1')
        ->and($article->source)->toBe('Test Source')
        ->and($article->api_provider)->toBe(NewsProviderEnum::NEWS_API);
});

test('job ignores duplicate articles based on slug', function () {
    // Create an existing article
    Article::factory()->create([
        'title' => 'Existing Article',
        'slug' => 'existing-article',
        'api_provider' => NewsProviderEnum::NEWS_API,
    ]);

    // Set up HTTP fake for the NewsAPI endpoint
    Http::fake([
        '*' => Http::response([
            'status' => 'ok',
            'totalResults' => 2,
            'articles' => [
                [
                    'title' => 'Existing Article', // Will create same slug
                    'description' => 'This is a duplicate',
                    'url' => 'https://example.com/duplicate',
                    'publishedAt' => now()->toIso8601String(),
                    'content' => 'Some content',
                    'source' => ['name' => 'Test Source'],
                    'author' => 'Test Author',
                    'urlToImage' => 'https://example.com/image.jpg',
                ],
                [
                    'title' => 'New Article',
                    'description' => 'This is new',
                    'url' => 'https://example.com/new',
                    'publishedAt' => now()->toIso8601String(),
                    'content' => 'New content',
                    'source' => ['name' => 'Test Source'],
                    'author' => 'Test Author',
                    'urlToImage' => 'https://example.com/new-image.jpg',
                ],
            ],
        ], 200),
    ]);

    // Execute the job
    (new FetchArticlesFromNewsApiJob)->handle();

    // Verify only the new article was added (total should be 2)
    expect(Article::count())->toBe(2)
        ->and(Article::where('title', 'New Article')->exists())->toBeTrue();
});

test('job handles chunking of large article collections', function () {
    // Generate 60 test articles for the response
    $articleData = [];
    for ($i = 1; $i <= 60; $i++) {
        $articleData[] = [
            'title' => "Test Article $i",
            'description' => "Description for test article $i",
            'url' => "https://example.com/article-$i",
            'publishedAt' => now()->subMinutes($i)->toIso8601String(),
            'content' => "Content for test article $i",
            'source' => ['name' => 'Test Source'],
            'author' => 'Test Author',
            'urlToImage' => "https://example.com/image-$i.jpg",
        ];
    }

    // Set up HTTP fake for the NewsAPI endpoint
    Http::fake([
        '*' => Http::response([
            'status' => 'ok',
            'totalResults' => 60,
            'articles' => $articleData,
        ], 200),
    ]);

    // Execute the job
    (new FetchArticlesFromNewsApiJob)->handle();

    // Verify all articles were saved despite chunking
    expect(Article::count())->toBe(60);
});

test('job limits title in slug to 120 characters', function () {
    // Create a very long title
    $longTitle = 'This is a very long title that exceeds the 120 character limit for slugs and should be truncated to fit within the limit of characters allowed for slugs.';

    expect(strlen($longTitle))->toBeGreaterThan(120);

    // Set up HTTP fake for the NewsAPI endpoint
    Http::fake([
        '*' => Http::response([
            'status' => 'ok',
            'totalResults' => 1,
            'articles' => [
                [
                    'title' => $longTitle,
                    'description' => 'Article with long title',
                    'url' => 'https://example.com/long-title',
                    'publishedAt' => now()->toIso8601String(),
                    'content' => 'Content for long title article',
                    'source' => ['name' => 'Test Source'],
                    'author' => 'Test Author',
                    'urlToImage' => 'https://example.com/long-title.jpg',
                ],
            ],
        ], 200),
    ]);

    // Execute the job
    (new FetchArticlesFromNewsApiJob)->handle();

    // Verify the slug was limited properly
    $article = Article::first();
    expect($article->slug)->not->toBeNull()
        ->and(strlen($article->slug))->toBeLessThan(121);
});

test('job handles API errors gracefully', function () {
    // Set up HTTP fake to simulate an API error
    Http::fake([
        '*' => Http::response([
            'status' => 'error',
            'code' => 'apiKeyInvalid',
            'message' => 'Your API key is invalid or incorrect.',
        ], 401),
    ]);

    // Execute the job and expect it not to throw an exception
    expect(fn () => (new FetchArticlesFromNewsApiJob)->handle())
        ->not->toThrow(\Exception::class);

    // Verify no articles were saved
    expect(Article::count())->toBe(0);
});
