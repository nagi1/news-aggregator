<?php

use App\Aggregator\Providers\NewsApi;
use App\Enums\NewsProviderEnum;
use App\Jobs\FetchArticlesJob;
use App\Models\Article;
use App\Models\User;
use App\Support\ArticleDto;
use App\Support\NewsProviderOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;

use function Pest\Laravel\mock;

beforeEach(function () {
    // Set fixed date for testing
    Carbon::setTestNow('2025-03-12 12:00:00');
});

it('creates articles from the news provider feed', function () {
    User::factory()->create([
        'preferences' => [
            'keywords' => ['laravel'],
            'categories' => ['tech'],
            'sources' => ['source1'],
            'authors' => ['author1'],
        ],
    ]);

    $articleDto = new ArticleDto(
        slug: 'test-article',
        title: 'Test Article',
        description: 'Test Description',
        url: 'http://example.com/article',
        publishedAt: Carbon::now()->subDay(),
        content: 'Test content',
        source: 'source1',
        author: 'author1',
        image: 'http://example.com/image.jpg',
        apiProvider: NewsProviderEnum::NEWS_API,
    );

    $providerMock = Mockery::mock(NewsApi::class);

    $providerMock->shouldReceive('fetchLatestNewsCursor')
        ->once()
        ->andReturn(new LazyCollection([$articleDto]));

    app()->bind(NewsApi::class, fn () => $providerMock);

    (new FetchArticlesJob(NewsProviderEnum::NEWS_API->value, 100))->handle();

    expect(Article::where('slug', 'test-article')->exists())->toBeTrue();
});

it('skips duplicate articles based on slug', function () {
    User::factory()->create([
        'preferences' => [
            'keywords' => ['laravel'],
            'categories' => ['tech'],
            'sources' => ['source1'],
            'authors' => ['author1'],
        ],
    ]);

    Article::create([
        'slug' => 'duplicate-article',
        'title' => 'Existing Article',
        'description' => 'Existing description',
        'url' => 'http://example.com/existing-article',
        'published_at' => Carbon::now()->subDay(),
        'content' => 'Existing content',
        'source' => 'source1',
        'author' => 'author1',
        'image' => 'http://example.com/existing-image.jpg',
        'api_provider' => NewsProviderEnum::NEWS_API,
    ]);

    $articleDto = new ArticleDto(
        slug: 'duplicate-article',
        title: 'New Duplicate Article',
        description: 'New duplicate description',
        url: 'http://example.com/new-duplicate-article',
        publishedAt: Carbon::now()->subDay(),
        content: 'New duplicate content',
        source: 'source1',
        author: 'author1',
        image: 'http://example.com/new-duplicate-image.jpg',
        apiProvider: NewsProviderEnum::NEWS_API,
    );

    $lazyCollection = new LazyCollection([$articleDto]);

    $providerMock = mock(NewsApi::class, function ($mock) use ($lazyCollection) {
        $mock->shouldReceive('fetchLatestNewsCursor')->once()->andReturn($lazyCollection);
    });

    app()->bind(NewsApi::class, fn () => $providerMock);

    (new FetchArticlesJob(NewsProviderEnum::NEWS_API->value, 100))->handle();

    expect(Article::where('slug', 'duplicate-article')->count())->toBe(1);
});

it('passes correct options to the news provider based on user preferences', function () {
    User::factory()->create([
        'preferences' => [
            'keywords' => ['laravel'],
            'categories' => ['tech'],
            'sources' => ['source1'],
            'authors' => ['author1'],
        ],
    ]);

    $capturedOptions = null;
    $lazyCollection = new LazyCollection;

    $providerMock = mock(NewsApi::class, function ($mock) use ($lazyCollection, &$capturedOptions) {
        $mock->shouldReceive('fetchLatestNewsCursor')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;

                return true;
            })
            ->andReturn($lazyCollection);
    });

    app()->bind(NewsApi::class, fn () => $providerMock);

    (new FetchArticlesJob(NewsProviderEnum::NEWS_API->value, 100))->handle();

    /** @var NewsProviderOptions $capturedOptions */
    expect($capturedOptions)->not->toBeNull();
    expect($capturedOptions->getLimit())->toBe(100);
    expect($capturedOptions->getKeywords())->toEqual(['laravel']);
    expect($capturedOptions->getCategories())->toEqual(['tech']);
    expect($capturedOptions->getSources())->toEqual(['source1']);
    expect($capturedOptions->getAuthors())->toEqual(['author1']);
    expect($capturedOptions->fromDate->equalTo(Carbon::now()->yesterday()))->toBeTrue();
    expect($capturedOptions->toDate->equalTo(Carbon::now()))->toBeTrue();
});

it('defaults user preferences to empty arrays when no preferences exist', function () {
    $capturedOptions = null;
    $lazyCollection = new LazyCollection;

    $providerMock = mock(NewsApi::class, function ($mock) use ($lazyCollection, &$capturedOptions) {
        $mock->shouldReceive('fetchLatestNewsCursor')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;

                return true;
            })
            ->andReturn($lazyCollection);
    });

    app()->bind(NewsApi::class, fn () => $providerMock);

    (new FetchArticlesJob(NewsProviderEnum::NEWS_API->value, 100))->handle();

    /** @var NewsProviderOptions $capturedOptions */
    expect($capturedOptions)->not->toBeNull();
    expect($capturedOptions->getKeywords())->toEqual([]);
    expect($capturedOptions->getCategories())->toEqual([]);
    expect($capturedOptions->getSources())->toEqual([]);
    expect($capturedOptions->getAuthors())->toEqual([]);
});
