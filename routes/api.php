<?php

use App\Http\Controllers\IndexArticleController;
use App\Http\Controllers\UserPreferenceController;
use Illuminate\Support\Facades\Route;

// Article
Route::get('articles', IndexArticleController::class);

// User Preferences
Route::resource('user-preferences', UserPreferenceController::class);
