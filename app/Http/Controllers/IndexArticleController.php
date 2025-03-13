<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;

class IndexArticleController extends Controller
{
    public function __invoke(IndexArticleRequest $request)
    {
        $usePreferences = ! $request->hasAnySearchCriteria();
        $limit = min($request->input('limit', 50), 100);
        $page = $request->input('page', 1);

        $articleQuery = $query = Article::query();

        if ($request->boolean('all', false) === false) {
            $query = $usePreferences
            ? $articleQuery->buildPreferenceQuery(auth()->user()->preferences)
            : $articleQuery->buildSearchQuery($request->validated());
        }

        if ($request->filled('date')) {
            $query = $articleQuery->applyDateFilter($request->date);
        }

        $articles = $query->paginate($limit, ['*'], 'page', $page);

        return ArticleResource::collection($articles);
    }
}
