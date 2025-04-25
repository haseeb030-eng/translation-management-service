<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    private static $languages = [
        'en' => 'English',
        'fr' => 'French',
        'es' => 'Spanish',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'zh' => 'Chinese',
        'ar' => 'Arabic',
        'hi' => 'Hindi',
        'ko' => 'Korean'
    ];

    public function definition()
    {
        // Get all codes that have already been used
        $usedCodes = Language::pluck('code')->toArray();

        // Filter out used codes from our predefined list
        $availableCodes = array_diff(array_keys(self::$languages), $usedCodes);

        if (empty($availableCodes)) {
            // If all predefined codes are used, generate a random one
            do {
                $code = strtolower($this->faker->unique()->lexify('??'));
            } while (in_array($code, $usedCodes));

            $name = $this->faker->unique()->word();
        } else {
            // Use a predefined language
            $code = $availableCodes[array_rand($availableCodes)];
            $name = self::$languages[$code];
        }

        return [
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ];
    }
}
