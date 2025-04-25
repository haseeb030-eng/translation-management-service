<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Export",
 *     description="Export endpoints for retrieving translations in different formats"
 * )
 */
class ExportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/export/{language}",
     *     tags={"Export"},
     *     summary="Export translations for a specific language",
     *     description="Returns translations for a specific language in a flat key-value format",
     *     @OA\Parameter(
     *         name="language",
     *         in="path",
     *         description="Language code (e.g., 'en', 'fr')",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translations exported successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="language",
     *                 type="object",
     *                 @OA\Property(property="code", type="string", example="en"),
     *                 @OA\Property(property="name", type="string", example="English")
     *             ),
     *             @OA\Property(
     *                 property="translations",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="key", type="string", example="welcome.message"),
     *                     @OA\Property(property="value", type="string", example="Welcome")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Language not found or inactive",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Language not found or inactive")
     *         )
     *     )
     * )
     */
    public function exportByLanguage(Request $request, $languageCode)
    {
        // Validate language exists
        $language = Language::where('code', $languageCode)->where('is_active', true)->first();

        if (!$language) {
            return response()->json(['error' => 'Language not found or inactive'], 404);
        }

        // Use cache with a short TTL (60 seconds) to ensure fresh data while reducing DB load
        $translations = Cache::remember("translations.export.{$languageCode}", 60, function () use ($language) {
            return Translation::where('language_id', $language->id)
                ->select('key', 'value')
                ->get()
                ->map(function ($item) {
                    return [
                        'key' => $item->key,
                        'value' => $item->value
                    ];
                });
        });

        return response()->json([
            'language' => [
                'code' => $language->code,
                'name' => $language->name,
            ],
            'translations' => $translations
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/export/{language}/nested",
     *     tags={"Export"},
     *     summary="Export translations in nested format",
     *     description="Returns translations for a specific language in a nested object structure",
     *     @OA\Parameter(
     *         name="language",
     *         in="path",
     *         description="Language code (e.g., 'en', 'fr')",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nested translations exported successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "menu": {
     *                     "home": {
     *                         "title": "Home",
     *                         "subtitle": "Welcome"
     *                     }
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Language not found or inactive",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Language not found or inactive")
     *         )
     *     )
     * )
     */
    public function exportNestedByLanguage(Request $request, $languageCode)
    {
        // Validate language exists
        $language = Language::where('code', $languageCode)->where('is_active', true)->first();

        if (!$language) {
            return response()->json(['error' => 'Language not found or inactive'], 404);
        }

        // Use cache with a short TTL to ensure fresh data
        $translations = Cache::remember("translations.export.nested.{$languageCode}", 60, function () use ($language) {
            // Optimize query with select
            $flatTranslations = Translation::select('key', 'value')
                ->where('language_id', $language->id)
                ->get()
                ->pluck('value', 'key')
                ->toArray();

            // Convert flat to nested structure
            $nested = [];

            foreach ($flatTranslations as $key => $value) {
                $this->setNestedValue($nested, $key, $value);
            }

            return $nested;
        });

        return response()->json($translations);
    }

    /**
     * @OA\Get(
     *     path="/api/export",
     *     tags={"Export"},
     *     summary="Export translations for multiple languages",
     *     description="Returns translations for multiple languages in a flat format",
     *     @OA\Parameter(
     *         name="languages",
     *         in="query",
     *         description="Comma-separated list of language codes (e.g., 'en,fr,de')",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Multiple languages exported successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "en": {
     *                     {"key": "welcome.message", "value": "Welcome"},
     *                     {"key": "goodbye.message", "value": "Goodbye"}
     *                 },
     *                 "fr": {
     *                     {"key": "welcome.message", "value": "Bienvenue"},
     *                     {"key": "goodbye.message", "value": "Au revoir"}
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The languages field is required."
     *             )
     *         )
     *     )
     * )
     */
    public function exportMultipleLanguages(Request $request)
    {
        $request->validate([
            'languages' => 'required|string',
        ]);

        $languageCodes = explode(',', $request->languages);
        $result = [];

        foreach ($languageCodes as $languageCode) {
            $language = Language::where('code', $languageCode)->where('is_active', true)->first();

            if (!$language) {
                continue;
            }

            // Use cache with a short TTL
            $translations = Cache::remember("translations.export.{$languageCode}", 60, function () use ($language) {
                return Translation::select('key', 'value')
                    ->where('language_id', $language->id)
                    ->get()
                    ->map(function ($translation) {
                        return [$translation->key, $translation->value];
                    })
                    ->values()
                    ->toArray();
            });

            $result[$languageCode] = $translations;
        }

        return response()->json($result);
    }

    /**
     * Helper function to convert flat key structure to nested objects
     */
    private function setNestedValue(&$array, $key, $value)
    {
        if (strpos($key, '.') === false) {
            $array[$key] = $value;
            return;
        }

        $parts = explode('.', $key, 2);
        $currentKey = $parts[0];
        $remainingKey = $parts[1];

        if (!isset($array[$currentKey]) || !is_array($array[$currentKey])) {
            $array[$currentKey] = [];
        }

        $this->setNestedValue($array[$currentKey], $remainingKey, $value);
    }
}
