<?php

namespace Tests\Unit;

use App\Models\Translation;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_has_fillable_attributes(): void
    {
        $language = Language::factory()->create();
        $translation = Translation::factory()->create([
            'language_id' => $language->id,
            'key' => 'test.key',
            'value' => 'Test Value'
        ]);

        $this->assertEquals($language->id, $translation->language_id);
        $this->assertEquals('test.key', $translation->key);
        $this->assertEquals('Test Value', $translation->value);
    }

    public function test_translation_belongs_to_language(): void
    {
        $translation = Translation::factory()->create();

        $this->assertInstanceOf(Language::class, $translation->language);
    }

    public function test_translation_can_have_many_tags(): void
    {
        $translation = Translation::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $translation->tags()->attach($tags->pluck('id'));

        $this->assertCount(3, $translation->tags);
        $this->assertInstanceOf(Tag::class, $translation->tags->first());
    }

    public function test_translation_key_must_be_unique_per_language(): void
    {
        $language = Language::factory()->create();
        Translation::factory()->create([
            'language_id' => $language->id,
            'key' => 'unique.key'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Translation::factory()->create([
            'language_id' => $language->id,
            'key' => 'unique.key'
        ]);
    }

    public function test_translation_factory_creates_valid_translation(): void
    {
        $translation = Translation::factory()->create();

        $this->assertNotNull($translation->key);
        $this->assertNotNull($translation->value);
        $this->assertNotNull($translation->language_id);
        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'key' => $translation->key,
            'value' => $translation->value,
            'language_id' => $translation->language_id
        ]);
    }

    public function test_can_sync_tags(): void
    {
        $translation = Translation::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        // Add initial tags
        $translation->tags()->sync($tags->pluck('id'));
        $this->assertCount(3, $translation->tags);

        // Sync with new set of tags
        $newTags = Tag::factory()->count(2)->create();
        $translation->tags()->sync($newTags->pluck('id'));
        $translation->refresh();

        $this->assertCount(2, $translation->tags);
        $this->assertEquals($newTags->pluck('id')->sort()->values(), $translation->tags->pluck('id')->sort()->values());
    }
}
