<?php

namespace App\Jobs;

use App\Aggregator\NewsProviderFactory;
use App\Aggregator\Providers\NewsApi;
use App\Enums\NewsProviderEnum;
use App\Models\Article;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\LazyCollection;

class FetchArticlesFromNewsApiJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        /** @var NewsApi */
        $newsApiProvider = NewsProviderFactory::make(NewsProviderEnum::NEWS_API);

        // A date and optional time for the oldest article allowed. This should be in ISO 8601 format for the last 20 minutes.
        $newsApiProvider->fetchLatestNewsCursor(
            fromDate: now()->subMinutes(20)->toIso8601String(),
            toDate: now()->toIso8601String(),
            limit: 100
        )
            ->chunk(50)
            ->each(function (LazyCollection $articlesBatch) {
                $articlesBatch = $articlesBatch->map(function (array $article) {
                    $article['slug'] = str($article['title'])->limit(120, '')->slug()->toString();

                    return $article;
                });

                $duplicateSlugs = Article::query()
                    ->where('api_provider', NewsProviderEnum::NEWS_API)
                    ->whereIn('slug', $articlesBatch->pluck('slug'))
                    ->pluck('slug');

                $articlesBatch
                    ->filter()
                    ->filter(function (array $article) use ($duplicateSlugs) {
                        return ! $duplicateSlugs->contains($article['slug']);
                    })
                    ->each(function (array $article) {
                        // Save the article to the database
                        Article::query()->create([
                            'slug' => $article['slug'],
                            'title' => $article['title'],
                            'description' => $article['description'],
                            'url' => $article['url'],
                            'published_at' => $article['publishedAt'],
                            'content' => $article['content'],
                            'source' => $article['source']['name'],
                            'author' => $article['author'],
                            'image' => $article['urlToImage'],
                            'api_provider' => NewsProviderEnum::NEWS_API,
                        ]);
                    });
            });
    }
}
