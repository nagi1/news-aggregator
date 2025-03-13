<?php

namespace App\Jobs;

use App\Aggregator\NewsProviderFactory;
use App\Enums\NewsProviderEnum;
use App\Models\Article;
use App\Models\User;
use App\Models\UserPreference;
use App\Support\ArticleDto;
use App\Support\NewsProviderOptions;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\LazyCollection;

class FetchArticlesJob implements ShouldQueue
{
    use Queueable;

    protected NewsProviderEnum $provider;

    public function __construct(string $provider, protected int $limit = 100)
    {
        $this->provider = NewsProviderEnum::from($provider);
    }

    public function handle(): void
    {
        $newsApiProvider = NewsProviderFactory::make($this->provider);

        $usersPreferences = $this->getUsersPreferences();

        $newsApiProvider->fetchLatestNewsCursor(
            new NewsProviderOptions(
                fromDate: now()->yesterday(),
                toDate: now(),
                limit: $this->limit,
                keywords: $usersPreferences['keywords'] ?? [],
                categories : $usersPreferences['categories'] ?? [],
                sources : $usersPreferences['sources'] ?? [],
                authors : $usersPreferences['authors'] ?? [],
            )
        )
            ->chunk(50)
            ->each(function (LazyCollection $articlesBatch) {
                $duplicateSlugs = Article::query()
                    ->where('api_provider', $this->provider)
                    ->whereIn('slug', $articlesBatch->map(fn (ArticleDto $article) => $article->slug)->toArray())
                    ->pluck('slug');

                $articlesBatch
                    ->filter()
                    ->filter(function (ArticleDto $article) use ($duplicateSlugs) {
                        return ! $duplicateSlugs->contains(fn (string $slug) => $slug === $article->slug);
                    })
                    ->each(function (ArticleDto $articleDto) {
                        Article::create([
                            'slug' => $articleDto->slug,
                            'title' => $articleDto->title,
                            'description' => $articleDto->description,
                            'url' => $articleDto->url,
                            'published_at' => $articleDto->publishedAt,
                            'content' => $articleDto->content,
                            'source' => $articleDto->source,
                            'author' => $articleDto->author,
                            'image' => $articleDto->image,
                            'api_provider' => $articleDto->apiProvider,
                        ]);
                    });
            });
    }

    private function getUsersPreferences(): array
    {
        $preferences = User::query()
            ->select('preferences')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        // merge preferences (keywords, categories, sources, authors) into a single array
        return $preferences->reduce(function (array $carry, User $user) {
            return array_merge($carry, [
                'keywords' => array_merge($carry['keywords'] ?? [], $user->preferences['keywords'] ?? []),
                'categories' => array_merge($carry['categories'] ?? [], $user->preferences['categories'] ?? []),
                'sources' => array_merge($carry['sources'] ?? [], $user->preferences['sources'] ?? []),
                'authors' => array_merge($carry['authors'] ?? [], $user->preferences['authors'] ?? []),
            ]);
        }, []);
    }
}
