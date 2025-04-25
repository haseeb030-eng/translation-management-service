<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create()->first();
    }

    public function test_can_list_tags(): void
    {
        Tag::factory()->count(3)->create();

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/tags');

        $response->assertOk()
                ->assertJsonCount(3);
    }

    public function test_tags_list_includes_translation_count(): void
    {
        $tag = Tag::factory()->create();
        $translations = Translation::factory()->count(2)->create();
        $tag->translations()->attach($translations->pluck('id'));

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/tags');

        $response->assertOk()
                ->assertJsonFragment([
                    'translations_count' => 2
                ]);
    }

    public function test_can_create_tag(): void
    {
        Sanctum::actingAs($this->user);

        $data = [
            'name' => 'new-tag'
        ];

        $response = $this->postJson('/api/tags', $data);

        $response->assertCreated()
                ->assertJsonFragment($data);

        $this->assertDatabaseHas('tags', $data);
    }

    public function test_cannot_create_duplicate_tag(): void
    {
        Tag::factory()->create(['name' => 'existing-tag']);

        Sanctum::actingAs($this->user);

        $data = [
            'name' => 'existing-tag'
        ];

        $response = $this->postJson('/api/tags', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    public function test_can_show_tag_with_translations(): void
    {
        $tag = Tag::factory()->create();
        $translations = Translation::factory()->count(2)->create();
        $tag->translations()->attach($translations->pluck('id'));

        Sanctum::actingAs($this->user);
        $response = $this->getJson("/api/tags/{$tag->id}");

        $response->assertOk()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'translations' => [
                        '*' => ['id', 'key', 'value']
                    ]
                ]);
    }

    public function test_can_update_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'old-name']);

        Sanctum::actingAs($this->user);

        $data = [
            'name' => 'updated-name'
        ];

        $response = $this->putJson("/api/tags/{$tag->id}", $data);

        $response->assertOk()
                ->assertJsonFragment($data);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'updated-name'
        ]);
    }

    public function test_cannot_update_to_existing_tag_name(): void
    {
        Tag::factory()->create(['name' => 'existing-name']);
        $tagToUpdate = Tag::factory()->create(['name' => 'original-name']);

        Sanctum::actingAs($this->user);

        $data = [
            'name' => 'existing-name'
        ];

        $response = $this->putJson("/api/tags/{$tagToUpdate->id}", $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    public function test_can_delete_tag(): void
    {
        $tag = Tag::factory()->create();
        $translations = Translation::factory()->count(2)->create();
        $tag->translations()->attach($translations->pluck('id'));

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/tags/{$tag->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('translation_tag', ['tag_id' => $tag->id]);
    }
}
