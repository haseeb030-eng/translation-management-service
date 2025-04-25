<?php

namespace Tests\Unit;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_has_fillable_attributes(): void
    {
        $language = Language::factory()->create([
            'code' => 'fr',
            'name' => 'French',
            'is_active' => true
        ]);

        $this->assertEquals('fr', $language->code);
        $this->assertEquals('French', $language->name);
        $this->assertTrue($language->is_active);
    }

    public function test_language_can_have_many_translations(): void
    {
        $language = Language::factory()->create();
        Translation::factory()->count(3)->create(['language_id' => $language->id]);

        $this->assertCount(3, $language->translations);
        $this->assertInstanceOf(Translation::class, $language->translations->first());
    }

    public function test_language_factory_creates_valid_language(): void
    {
        $language = Language::factory()->create();

        $this->assertNotNull($language->code);
        $this->assertNotNull($language->name);
        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'code' => $language->code,
            'name' => $language->name
        ]);
    }
}
