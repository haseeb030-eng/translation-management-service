<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var User $user */
        $this->user = User::factory()->create()->first();
        $this->language = Language::factory()->create(['code' => 'en']);
    }

    public function test_can_export_translations_by_language(): void
    {
        Translation::factory()->count(3)->create([
            'language_id' => $this->language->id,
        ]);

        $response = $this->getJson("/api/export/{$this->language->code}");

        $response->assertOk()
            ->assertJsonStructure([
                'language' => ['code', 'name'],
                'translations' => [
                    '*' => ['key', 'value']
                ]
            ])
            ->assertJsonCount(3, 'translations');
    }

    public function test_cannot_export_translations_for_inactive_language(): void
    {
        $inactiveLanguage = Language::factory()->create([
            'code' => 'fr',
            'is_active' => false
        ]);

        $response = $this->getJson("/api/export/{$inactiveLanguage->code}");

        $response->assertNotFound()
            ->assertJsonStructure(['error']);
    }

    public function test_can_export_nested_translations_by_language(): void
    {
        Translation::factory()->create([
            'language_id' => $this->language->id,
            'key' => 'menu.home.title',
            'value' => 'Home'
        ]);

        Translation::factory()->create([
            'language_id' => $this->language->id,
            'key' => 'menu.home.subtitle',
            'value' => 'Welcome'
        ]);

        $response = $this->getJson("/api/export/{$this->language->code}/nested");

        $response->assertOk()
            ->assertJsonStructure([
                'menu' => [
                    'home' => [
                        'title',
                        'subtitle'
                    ]
                ]
            ]);
    }

    public function test_cannot_export_nested_translations_for_inactive_language(): void
    {
        $inactiveLanguage = Language::factory()->create([
            'code' => 'fr',
            'is_active' => false
        ]);

        $response = $this->getJson("/api/export/{$inactiveLanguage->code}/nested");

        $response->assertNotFound()
            ->assertJsonStructure(['error']);
    }

    public function test_can_export_multiple_languages(): void
    {
        $secondLanguage = Language::factory()->create(['code' => 'de']);

        // Create translations with specific values for testing
        Translation::create([
            'language_id' => $this->language->id,
            'key' => 'test.key1',
            'value' => 'Hello'
        ]);
        Translation::create([
            'language_id' => $this->language->id,
            'key' => 'test.key2',
            'value' => 'World'
        ]);

        Translation::create([
            'language_id' => $secondLanguage->id,
            'key' => 'test.key1',
            'value' => 'Hallo'
        ]);
        Translation::create([
            'language_id' => $secondLanguage->id,
            'key' => 'test.key2',
            'value' => 'Welt'
        ]);

        $languages = "{$this->language->code},{$secondLanguage->code}";
        $response = $this->getJson("/api/export?languages={$languages}");

        $response->assertOk();

        // Verify both languages are present
        $data = $response->json();
        $this->assertArrayHasKey('en', $data);
        $this->assertArrayHasKey('de', $data);

        // Verify each language has the correct translations
        $this->assertCount(2, $data['en']);
        $this->assertCount(2, $data['de']);

        // Verify the structure follows key-value pairs
        foreach ($data as $langData) {
            foreach ($langData as $translation) {
                $this->assertCount(2, $translation); // Each translation should have key and value
                $this->assertContains($translation[0], ['test.key1', 'test.key2']);
                $this->assertContains($translation[1], ['Hello', 'World', 'Hallo', 'Welt']);
            }
        }
    }

    public function test_export_multiple_languages_skips_inactive_languages(): void
    {
        $inactiveLanguage = Language::factory()->create([
            'code' => 'fr',
            'is_active' => false
        ]);

        Translation::factory()->count(2)->create([
            'language_id' => $this->language->id,
        ]);

        $languages = "{$this->language->code},{$inactiveLanguage->code}";
        $response = $this->getJson("/api/export?languages={$languages}");

        $response->assertOk()
            ->assertJsonStructure(['en'])
            ->assertJsonMissing(['fr']);
    }

    public function test_export_multiple_languages_validates_languages_parameter(): void
    {
        $response = $this->getJson('/api/export');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['languages']);
    }
}
