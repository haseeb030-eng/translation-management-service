<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Translation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Model|User $user;
    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->language = Language::factory()->create();
    }

    public function test_can_list_translations(): void
    {
        Translation::factory()->count(3)->create();

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/translations');

        $response->assertOk()
                ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_translations_by_language(): void
    {
        $language = Language::factory()->create(['code' => 'es']);

        // Create exactly two translations for Spanish
        Translation::create([
            'language_id' => $language->id,
            'key' => 'test.key1',
            'value' => 'Hola'
        ]);
        Translation::create([
            'language_id' => $language->id,
            'key' => 'test.key2',
            'value' => 'Mundo'
        ]);

        // Create a translation for a different language
        Translation::create([
            'language_id' => $this->language->id,
            'key' => 'test.key3',
            'value' => 'Hello'
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/translations?language=es');

        $response->assertOk()
                ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_translations_by_key(): void
    {
        Translation::factory()->create(['key' => 'test.search.key']);
        Translation::factory()->create(['key' => 'other.key']);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/translations?key=search');

        $response->assertOk()
                ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_translations_by_value(): void
    {
        Translation::factory()->create(['value' => 'Find this text']);
        Translation::factory()->create(['value' => 'Other text']);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/translations?value=Find');

        $response->assertOk()
                ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_translations_by_tags(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::factory()->create(['name' => 'mobile']);
        $translation->tags()->attach($tag->id);

        Translation::factory()->create(); // without tag

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/translations?tags=mobile');

        $response->assertOk()
                ->assertJsonCount(1, 'data');
    }

    public function test_can_create_translation(): void
    {
        Sanctum::actingAs($this->user);

        $data = [
            'language_id' => $this->language->id,
            'key' => 'test.create.key',
            'value' => 'Test Value',
            'tags' => ['mobile', 'web']
        ];

        $response = $this->postJson('/api/translations', $data);

        $response->assertCreated()
                ->assertJsonFragment([
                    'key' => 'test.create.key',
                    'value' => 'Test Value'
                ]);

        $this->assertDatabaseHas('translations', [
            'language_id' => $this->language->id,
            'key' => 'test.create.key',
            'value' => 'Test Value'
        ]);

        // Verify tags were created and attached
        $translation = Translation::where('key', 'test.create.key')->first();
        $this->assertCount(2, $translation->tags);
    }

    public function test_cannot_create_duplicate_translation_key_for_same_language(): void
    {
        Translation::factory()->create([
            'language_id' => $this->language->id,
            'key' => 'test.duplicate.key'
        ]);

        Sanctum::actingAs($this->user);

        $data = [
            'language_id' => $this->language->id,
            'key' => 'test.duplicate.key',
            'value' => 'New Value'
        ];

        $response = $this->postJson('/api/translations', $data);

        $response->assertStatus(422);
    }

    public function test_can_update_translation(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::factory()->create(['name' => 'new-tag']);

        Sanctum::actingAs($this->user);

        $data = [
            'value' => 'Updated Value',
            'tags' => ['new-tag']
        ];

        $response = $this->putJson("/api/translations/{$translation->id}", $data);

        $response->assertOk()
                ->assertJsonFragment([
                    'value' => 'Updated Value'
                ]);

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'value' => 'Updated Value'
        ]);

        $translation->refresh();
        $this->assertCount(1, $translation->tags);
        $this->assertEquals('new-tag', $translation->tags->first()->name);
    }

    public function test_can_delete_translation(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::factory()->create();
        $translation->tags()->attach($tag->id);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/translations/{$translation->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
        $this->assertDatabaseMissing('translation_tag', [
            'translation_id' => $translation->id,
            'tag_id' => $tag->id
        ]);
    }
}
