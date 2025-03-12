<?php

use App\Http\Controllers\IndexArticleController;
use Illuminate\Support\Facades\Route;

// Article
Route::get('articles', IndexArticleController::class);

// User Preferences
// Route::get('user-preferences', IndexUserPreferenceController::class);
// Route::post('user-preferences', StoreUserPreferenceController::class);

// Route::get('article-categories', IndexArticleCategoryController::class);

// Route::get('sources', IndexSourceController::class);
