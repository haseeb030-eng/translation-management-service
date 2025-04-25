<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Translation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create()->first();
        $this->language = Language::factory()->create();
    }

    public function test_can_list_translations(): void
    {
        Translation::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/translations');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_translation(): void
    {
        $data = [
            'language_id' => $this->language->id,
            'key' => 'test.key',
            'value' => 'Test Value',
            'tags' => ['test', 'new']
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/translations', $data);

        $response->assertCreated();
        $this->assertDatabaseHas('translations', [
            'language_id' => $this->language->id,
            'key' => 'test.key',
            'value' => 'Test Value'
        ]);
    }

    public function test_cannot_create_duplicate_translation(): void
    {
        $translation = Translation::factory()->create([
            'language_id' => $this->language->id,
            'key' => 'test.key'
        ]);

        $data = [
            'language_id' => $this->language->id,
            'key' => 'test.key',
            'value' => 'New Value'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/translations', $data);

        $response->assertStatus(422);
    }

    public function test_can_update_translation(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::factory()->create();

        $data = [
            'value' => 'Updated Value',
            'tags' => [$tag->name]
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/translations/{$translation->id}", $data);

        $response->assertOk();
        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'value' => 'Updated Value'
        ]);
    }

    public function test_can_search_translations(): void
    {
        Translation::factory()->create([
            'key' => 'test.search.key',
            'value' => 'Searchable Value'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/translations/search?query=search');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_delete_translation(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/translations/{$translation->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }
}
