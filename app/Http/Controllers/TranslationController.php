<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Schema(
 *     schema="Translation",
 *     required={"language_id", "key", "value"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="language_id", type="integer", example=1),
 *     @OA\Property(property="key", type="string", example="welcome.message"),
 *     @OA\Property(property="value", type="string", example="Welcome to our application"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="language", ref="#/components/schemas/Language"),
 *     @OA\Property(
 *         property="tags",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="name", type="string")
 *         )
 *     )
 * )
 */
class TranslationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/translations",
     *     tags={"Translations"},
     *     summary="List all translations",
     *     description="Returns a paginated list of translations with optional filters",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="query",
     *         description="Filter by translation key",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="value",
     *         in="query",
     *         description="Filter by translation value",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Filter by language code",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="tags",
     *         in="query",
     *         description="Filter by tags (comma-separated)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of translations",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Translation")),
     *             @OA\Property(property="first_page_url", type="string"),
     *             @OA\Property(property="from", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="last_page_url", type="string"),
     *             @OA\Property(property="next_page_url", type="string", nullable=true),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true),
     *             @OA\Property(property="to", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Translation::with(['language', 'tags']);

        // Filter by key
        if ($request->has('key')) {
            $query->where('key', 'like', '%' . $request->key . '%');
        }

        // Filter by value
        if ($request->has('value')) {
            $query->where('value', 'like', '%' . $request->value . '%');
        }

        // Filter by language
        if ($request->has('language')) {
            $query->whereHas('language', function ($q) use ($request) {
                $q->where('code', $request->language);
            });
        }

        // Filter by tags
        if ($request->has('tags')) {
            $tags = explode(',', $request->tags);
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        // Use pagination for better performance with large datasets
        $translations = $query->paginate(50);

        return response()->json($translations);
    }

    /**
     * @OA\Post(
     *     path="/api/translations",
     *     tags={"Translations"},
     *     summary="Create a new translation",
     *     description="Creates a new translation with optional tags",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"language_id", "key", "value"},
     *             @OA\Property(property="language_id", type="integer", example=1),
     *             @OA\Property(property="key", type="string", example="welcome.message"),
     *             @OA\Property(property="value", type="string", example="Welcome to our application"),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"common", "frontend"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Translation created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Translation")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or duplicate key"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'language_id' => 'required|exists:languages,id',
            'key' => 'required|string',
            'value' => 'required|string',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
        ]);

        // Begin transaction for atomicity
        DB::beginTransaction();

        try {
            // Check if translation already exists
            $existingTranslation = Translation::where('language_id', $validated['language_id'])
                ->where('key', $validated['key'])
                ->first();

            if ($existingTranslation) {
                return response()->json(['message' => 'Translation key already exists for this language'], 422);
            }

            // Create the translation
            $translation = Translation::create([
                'language_id' => $validated['language_id'],
                'key' => $validated['key'],
                'value' => $validated['value'],
            ]);

            // Handle tags
            if (isset($validated['tags']) && !empty($validated['tags'])) {
                $tagIds = [];

                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }

                $translation->tags()->sync($tagIds);
            }

            DB::commit();

            // Clear related caches
            $this->clearCaches();

            return response()->json($translation->load('tags'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating translation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/translations/{translation}",
     *     tags={"Translations"},
     *     summary="Get a specific translation",
     *     description="Returns details of a specific translation including language and tags",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="translation",
     *         in="path",
     *         description="Translation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation details",
     *         @OA\JsonContent(ref="#/components/schemas/Translation")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Translation not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(Translation $translation)
    {
        return response()->json($translation->load(['language', 'tags']));
    }

    /**
     * @OA\Put(
     *     path="/api/translations/{translation}",
     *     tags={"Translations"},
     *     summary="Update a translation",
     *     description="Updates an existing translation's value and/or tags",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="translation",
     *         in="path",
     *         description="Translation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="string", example="Updated welcome message"),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"common", "updated"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Translation")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Translation not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function update(Request $request, Translation $translation)
    {
        $validated = $request->validate([
            'value' => 'sometimes|string',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
        ]);

        // Begin transaction for atomicity
        DB::beginTransaction();

        try {
            // Update translation value if provided
            if (isset($validated['value'])) {
                $translation->value = $validated['value'];
                $translation->save();
            }

            // Handle tags if provided
            if (isset($validated['tags'])) {
                $tagIds = [];

                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }

                $translation->tags()->sync($tagIds);
            }

            DB::commit();

            // Clear related caches
            $this->clearCaches();

            return response()->json($translation->load('tags'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating translation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/translations/{translation}",
     *     tags={"Translations"},
     *     summary="Delete a translation",
     *     description="Deletes a translation and its tag associations",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="translation",
     *         in="path",
     *         description="Translation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Translation deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Translation not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function destroy(Translation $translation)
    {
        DB::beginTransaction();

        try {
            $translation->tags()->detach();
            $translation->delete();

            DB::commit();

            // Clear related caches
            $this->clearCaches();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting translation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/translations/search",
     *     tags={"Translations"},
     *     summary="Search translations",
     *     description="Search translations by key, value with optional language filter",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query (minimum 2 characters)",
     *         required=true,
     *         @OA\Schema(type="string", minLength=2)
     *     ),
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Filter by language code",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Translation")),
     *             @OA\Property(property="first_page_url", type="string"),
     *             @OA\Property(property="from", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="last_page_url", type="string"),
     *             @OA\Property(property="next_page_url", type="string", nullable=true),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true),
     *             @OA\Property(property="to", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'language' => 'sometimes|string|exists:languages,code',
        ]);

        $query = Translation::with(['language', 'tags']);

        // Text search (optimized query)
        $searchTerm = $request->input('query');
        $query->where(function ($q) use ($searchTerm) {
            $q->where('key', 'like', "%{$searchTerm}%")
              ->orWhere('value', 'like', "%{$searchTerm}%");
        });

        // Filter by language if provided
        if ($request->has('language')) {
            $query->whereHas('language', function ($q) use ($request) {
                $q->where('code', $request->language);
            });
        }

        // For better performance, use pagination
        $translations = $query->paginate(50);

        return response()->json($translations);
    }

    /**
     * Clear all translation-related caches
     */
    protected function clearCaches()
    {
        // Clear export caches for all languages
        $languages = Language::pluck('code')->toArray();
        foreach ($languages as $languageCode) {
            Cache::forget("translations.export.{$languageCode}");
        }

        // Clear any other translation-related caches
        Cache::forget('translations.all');
    }
}
