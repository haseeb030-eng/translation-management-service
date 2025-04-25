<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
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

        $response = $this->actingAs($this->user)
            ->getJson('/api/tags');

        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_can_create_tag(): void
    {
        $data = ['name' => 'test-tag'];

        $response = $this->actingAs($this->user)
            ->postJson('/api/tags', $data);

        $response->assertCreated();
        $this->assertDatabaseHas('tags', ['name' => 'test-tag']);
    }

    public function test_cannot_create_duplicate_tag(): void
    {
        Tag::factory()->create(['name' => 'test-tag']);

        $data = ['name' => 'test-tag'];

        $response = $this->actingAs($this->user)
            ->postJson('/api/tags', $data);

        $response->assertStatus(422);
    }

    public function test_can_update_tag(): void
    {
        $tag = Tag::factory()->create();

        $data = ['name' => 'updated-tag'];

        $response = $this->actingAs($this->user)
            ->putJson("/api/tags/{$tag->id}", $data);

        $response->assertOk();
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'updated-tag'
        ]);
    }

    public function test_can_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tags/{$tag->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
