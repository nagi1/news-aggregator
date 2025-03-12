<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IndexArticleController extends Controller
{
    public function __invoke(Request $request)
    {
        return ArticleResource::collection(
            QueryBuilder::for(Article::class)
                ->allowedFilters([
                    AllowedFilter::partial('title'),
                    AllowedFilter::partial('category'),
                    AllowedFilter::partial('source'),
                    AllowedFilter::scope('date'),
                ])
                ->allowedSorts('title', 'published_at')
                ->defaultSort('published_at')
                ->paginate()
        );
    }
}
