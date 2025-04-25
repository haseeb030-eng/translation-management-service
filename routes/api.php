<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ExportController;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Export endpoints (can be public or protected based on your requirements)
Route::get('export/{language}', [ExportController::class, 'exportByLanguage']);
Route::get('export/{language}/nested', [ExportController::class, 'exportNestedByLanguage']);
Route::get('export', [ExportController::class, 'exportMultipleLanguages']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    // Languages
    Route::apiResource('languages', LanguageController::class);

    // Translations - specific routes first
    Route::get('translations/search', [TranslationController::class, 'search']);
    Route::apiResource('translations', TranslationController::class);

    // Tags
    Route::apiResource('tags', TagController::class);
});
