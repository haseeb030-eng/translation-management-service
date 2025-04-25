<?php

namespace Database\Factories;

use App\Models\Translation;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition()
    {
        static $counter = 0;
        static $usedKeys = [];

        // Get a random language or create one if none exists
        $language = Language::inRandomOrder()->first() ?? Language::factory()->create();

        // Generate a unique key for this language
        do {
            $prefixes = ['common', 'auth', 'errors', 'navigation', 'buttons'];
            $prefix = $prefixes[array_rand($prefixes)];
            $section = 'section_' . rand(1, 5);
            $item = 'item_' . ++$counter;
            $key = $prefix . '.' . $section . '.' . $item;
            $langKey = $language->id . '-' . $key;
        } while (in_array($langKey, $usedKeys));

        $usedKeys[] = $langKey;

        return [
            'language_id' => $language->id,
            'key' => $key,
            'value' => "Translation for {$key} in {$language->name}",
        ];
    }
}
