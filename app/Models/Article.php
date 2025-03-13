<?php

namespace App\Models;

use App\Enums\NewsProviderEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;

    protected function casts()
    {
        return [
            'published_at' => 'datetime',
            'api_provider' => NewsProviderEnum::class,
        ];
    }

    protected function scopeBuildPreferenceQuery(Builder $query, array $preferences): Builder
    {
        return $query->unless(empty($preferences['keywords']), function (Builder $query) use (&$preferences) {
            return $query->where(function (Builder $query) use (&$preferences) {
                foreach ($preferences['keywords'] as $keyword) {
                    $query->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('content', 'like', "%{$keyword}%");
                }
            });
        })
            ->unless(empty($preferences['sources']), function (Builder $query) use (&$preferences) {
                return $query->whereIn('source', $preferences['sources']);
            })
            ->unless(empty($preferences['categories']), function (Builder $query) use (&$preferences) {
                return $query->whereIn('category', $preferences['categories']);
            });
    }

    protected function scopeBuildSearchQuery(Builder $query, array $searchOptions): Builder
    {
        return $query->unless(empty($searchOptions['search']), function (Builder $query) use (&$searchOptions) {
            return $query->where(function ($q) use (&$searchOptions) {
                return $q->where('title', 'like', "%{$searchOptions['search']}%")
                    ->orWhere('description', 'like', "%{$searchOptions['search']}%")
                    ->orWhere('content', 'like', "%{$searchOptions['search']}%");
            });

        })
            ->unless(empty($searchOptions['source']), function (Builder $query) use (&$searchOptions) {
                return $query->where('source', $searchOptions['source']);
            })
            ->unless(empty($searchOptions['category']), function (Builder $query) use (&$searchOptions) {
                return $query->where('category', $searchOptions['category']);
            });
    }

    protected function scopeApplyDateFilter(Builder $query, string $date): Builder
    {
        return $query->whereDate('published_at', $date);
    }
}
