<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test user
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Get or create a language
    $language = Language::where('code', 'de')->first() ??
        Language::factory()->create([
            'code' => 'de',
            'name' => 'German'
        ]);

    // Generate translations with unique keys
    $translations = [];

    for ($i = 0; $i < 1000; $i++) {
        $translations[] = [
            'language_id' => $language->id,
            'key' => "test.section_{$i}.key_{$i}", // Ensure unique keys
            'value' => "Translation {$i} in {$language->name}",
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // Insert all translations at once
    Translation::insert($translations);
});

it('export endpoint responds in under 500ms with large dataset', function () {
    // Create a language if none exists
    $language = Language::first() ?? Language::factory()->create(['code' => 'en']);

    // Measure response time
    $startTime = microtime(true);

    $response = $this->getJson("/api/export/{$language->code}");

    $endTime = microtime(true);
    $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

    // Assert the response is successful
    $response->assertStatus(200);

    // Assert the response time is under 500ms
    $this->assertLessThan(500, $responseTime, "Export endpoint response time ({$responseTime}ms) exceeds 500ms");
});

it('translation search responds in under 200ms', function () {
    // Create a translation if none exists
    if (Translation::count() == 0) {
        Translation::factory()->create(['key' => 'test.search.key']);
    }

    // Measure response time
    $startTime = microtime(true);

    $response = $this->getJson('/api/translations/search?query=test');

    $endTime = microtime(true);
    $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

    // Assert the response is successful
    $response->assertStatus(200);

    // Assert the response time is under 200ms
    $this->assertLessThan(200, $responseTime, "Search endpoint response time ({$responseTime}ms) exceeds 200ms");
});

it('translation listing responds in under 200ms', function () {
    // Measure response time
    $startTime = microtime(true);

    $response = $this->getJson('/api/translations');

    $endTime = microtime(true);
    $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

    // Assert the response is successful
    $response->assertStatus(200);

    // Assert the response time is under 200ms
    $this->assertLessThan(200, $responseTime, "Translations listing response time ({$responseTime}ms) exceeds 200ms");
});
