<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Schema(
 *     schema="Language",
 *     required={"code", "name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", maxLength=10, example="en"),
 *     @OA\Property(property="name", type="string", maxLength=50, example="English"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class LanguageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/languages",
     *     tags={"Languages"},
     *     summary="List all languages",
     *     description="Returns a list of all languages in the system",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of languages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Language")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        $languages = Cache::remember('languages', 3600, function () {
            return Language::all();
        });

        return response()->json($languages);
    }

    /**
     * @OA\Post(
     *     path="/api/languages",
     *     tags={"Languages"},
     *     summary="Create a new language",
     *     description="Creates a new language in the system",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "name"},
     *             @OA\Property(property="code", type="string", maxLength=10, example="fr"),
     *             @OA\Property(property="name", type="string", maxLength=50, example="French"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Language created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Language")
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:languages',
            'name' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $language = Language::create($validated);

        // Clear cache when data changes
        Cache::forget('languages');

        return response()->json($language, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/languages/{language}",
     *     tags={"Languages"},
     *     summary="Get a specific language",
     *     description="Returns details of a specific language",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="language",
     *         in="path",
     *         description="Language ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Language details",
     *         @OA\JsonContent(ref="#/components/schemas/Language")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Language not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(Language $language)
    {
        return response()->json($language);
    }

    /**
     * @OA\Put(
     *     path="/api/languages/{language}",
     *     tags={"Languages"},
     *     summary="Update a language",
     *     description="Updates an existing language",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="language",
     *         in="path",
     *         description="Language ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=50, example="French"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Language updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Language")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Language not found"
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
    public function update(Request $request, Language $language)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $language->update($validated);

        // Clear cache when data changes
        Cache::forget('languages');

        return response()->json($language);
    }

    /**
     * @OA\Delete(
     *     path="/api/languages/{language}",
     *     tags={"Languages"},
     *     summary="Delete a language",
     *     description="Deletes a language from the system",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="language",
     *         in="path",
     *         description="Language ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Language deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Language not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy(Language $language)
    {
        $language->delete();

        // Clear cache when data changes
        Cache::forget('languages');

        return response()->json(null, 204);
    }
}
