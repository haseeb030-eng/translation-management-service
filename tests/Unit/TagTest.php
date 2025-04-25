<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_has_fillable_name(): void
    {
        $tag = Tag::factory()->create(['name' => 'test-tag']);

        $this->assertEquals('test-tag', $tag->name);
    }

    public function test_tag_can_have_many_translations(): void
    {
        $tag = Tag::factory()->create();
        $translations = Translation::factory()->count(3)->create();

        $tag->translations()->attach($translations->pluck('id'));

        $this->assertCount(3, $tag->translations);
        $this->assertInstanceOf(Translation::class, $tag->translations->first());
    }

    public function test_tag_factory_creates_valid_tag(): void
    {
        $tag = Tag::factory()->create();

        $this->assertNotNull($tag->name);
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => $tag->name
        ]);
    }

    public function test_tag_name_must_be_unique(): void
    {
        Tag::factory()->create(['name' => 'unique-tag']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Tag::factory()->create(['name' => 'unique-tag']);
    }
}
