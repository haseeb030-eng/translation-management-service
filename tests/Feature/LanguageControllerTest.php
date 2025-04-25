<?php

use App\Models\Language;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns all languages', function () {
    // // Create languages with guaranteed unique codes
    Language::factory()->create(['code' => 'test1', 'name' => 'Test Language 1']);
    Language::factory()->create(['code' => 'test2', 'name' => 'Test Language 2']);
    Language::factory()->create(['code' => 'test3', 'name' => 'Test Language 3']);

    Sanctum::actingAs(User::factory()->create());
    $response = $this->getJson('/api/languages');
    $response->assertStatus(200)
             ->assertJsonCount(3);
});

it('can create a language', function () {
    Sanctum::actingAs(User::factory()->create());

    // Ensure the language code doesn't exist
    $existingCodes = Language::pluck('code')->toArray();
    $availableCodes = ['de', 'fr', 'es', 'it', 'pt'];
    $code = collect($availableCodes)->first(fn($code) => !in_array($code, $existingCodes)) ?? 'test';

    $languageData = [
        'code' => $code,
        'name' => 'Test Language',
        'is_active' => true
    ];

    $response = $this->postJson('/api/languages', $languageData);

    $response->assertStatus(201)
             ->assertJsonFragment($languageData);

    $this->assertDatabaseHas('languages', $languageData);
});

it('can update a language', function () {
    $language = Language::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $updatedData = [
        'name' => 'Updated Language Name',
    ];

    $response = $this->putJson("/api/languages/{$language->id}", $updatedData);

    $response->assertStatus(200)
             ->assertJsonFragment($updatedData);

    $this->assertDatabaseHas('languages', [
        'id' => $language->id,
        'name' => 'Updated Language Name',
    ]);
});

it('can delete a language', function () {
    $language = Language::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $response = $this->deleteJson("/api/languages/{$language->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('languages', [
        'id' => $language->id,
    ]);
});
