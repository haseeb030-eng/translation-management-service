<?php

namespace Tests\Unit;

use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var User $user */
        $user = User::factory()->create();
        $this->user = $user;

        /** @var Language $language */
        $language = Language::factory()->create(['code' => 'en']);
        $this->language = $language;
    }

    public function test_can_export_translations_by_language(): void
    {
        Translation::factory()->count(3)->create([
            'language_id' => $this->language->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/export/{$this->language->code}");

        $response->assertOk()
            ->assertJsonStructure([
                'language' => ['code', 'name'],
                'translations' => [
                    '*' => ['key', 'value']
                ]
            ])
            ->assertJsonCount(3, 'translations');
    }

    public function test_can_export_all_translations(): void
    {
        $languages = [
            Language::factory()->create(['code' => 'it']),
            Language::factory()->create(['code' => 'pt'])
        ];

        foreach ($languages as $index => $language) {
            Translation::create([
                'language_id' => $language->id,
                'key' => "test.key.{$index}_1",
                'value' => "Value {$index}_1"
            ]);
            Translation::create([
                'language_id' => $language->id,
                'key' => "test.key.{$index}_2",
                'value' => "Value {$index}_2"
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/export?languages=' . collect($languages)->pluck('code')->join(','));

        $response->assertOk();

        $responseData = $response->json();

        foreach ($languages as $language) {
            $this->assertArrayHasKey($language->code, $responseData);
            $translations = $responseData[$language->code];
            $this->assertIsArray($translations);
            $this->assertNotEmpty($translations);
        }
    }
}
