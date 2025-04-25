<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create()->first();
    }

    public function test_can_list_languages(): void
    {
        // Create languages with unique codes
        $codes = ['test1', 'test2', 'test3'];
        foreach ($codes as $code) {
            Language::factory()->create([
                'code' => $code,
                'name' => "Test Language {$code}"
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/languages');

        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_can_create_language(): void
    {
        $data = [
            'code' => 'test-lang',
            'name' => 'Test Language',
            'is_active' => true
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/languages', $data);

        $response->assertCreated();
        $this->assertDatabaseHas('languages', [
            'code' => 'test-lang',
            'name' => 'Test Language'
        ]);
    }

    public function test_cannot_create_duplicate_language_code(): void
    {
        Language::factory()->create(['code' => 'fr']);

        $data = [
            'code' => 'fr',
            'name' => 'French',
            'is_active' => true
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/languages', $data);

        $response->assertStatus(422);
    }

    public function test_can_update_language(): void
    {
        $language = Language::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'is_active' => false
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/languages/{$language->id}", $data);

        $response->assertOk();
        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'name' => 'Updated Name',
            'is_active' => false
        ]);
    }

    public function test_can_delete_language(): void
    {
        $language = Language::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/languages/{$language->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('languages', ['id' => $language->id]);
    }
}
