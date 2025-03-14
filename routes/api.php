<?php

use App\Http\Controllers\IndexArticleController;
use App\Http\Controllers\UserPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Article
    Route::get('articles', IndexArticleController::class)->name('articles.index');

    // User Preferences
    Route::get('user-preferences', [UserPreferenceController::class, 'index'])->name('user-preferences.index');
    Route::put('user-preferences', [UserPreferenceController::class, 'update'])->name('user-preferences.update');
});
