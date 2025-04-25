<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Schema(
 *     schema="Tag",
 *     required={"name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="common"),
 *     @OA\Property(property="translations_count", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class TagController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tags",
     *     tags={"Tags"},
     *     summary="List all tags",
     *     description="Returns a list of all tags with their translation counts",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of tags",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Tag")
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
        $tags = Cache::remember('tags.all', 3600, function () {
            return Tag::withCount('translations')->get();
        });

        return response()->json($tags);
    }

    /**
     * @OA\Post(
     *     path="/api/tags",
     *     tags={"Tags"},
     *     summary="Create a new tag",
     *     description="Creates a new tag in the system",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="frontend")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tag created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Tag")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The name has already been taken."),
     *             @OA\Property(property="errors", type="object")
     *         )
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
            'name' => 'required|string|unique:tags',
        ]);

        $tag = Tag::create($validated);

        // Clear cache
        Cache::forget('tags.all');

        return response()->json($tag, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/tags/{tag}",
     *     tags={"Tags"},
     *     summary="Get a specific tag",
     *     description="Returns details of a specific tag including its translations",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tag",
     *         in="path",
     *         description="Tag ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag details",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/Tag"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="translations",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Translation")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(Tag $tag)
    {
        return response()->json($tag->load('translations'));
    }

    /**
     * @OA\Put(
     *     path="/api/tags/{tag}",
     *     tags={"Tags"},
     *     summary="Update a tag",
     *     description="Updates an existing tag",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tag",
     *         in="path",
     *         description="Tag ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="updated-tag")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Tag")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found"
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
    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:tags,name,' . $tag->id,
        ]);

        $tag->update($validated);

        // Clear cache
        Cache::forget('tags.all');

        return response()->json($tag);
    }

    /**
     * @OA\Delete(
     *     path="/api/tags/{tag}",
     *     tags={"Tags"},
     *     summary="Delete a tag",
     *     description="Deletes a tag from the system",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tag",
     *         in="path",
     *         description="Tag ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Tag deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        // Clear cache
        Cache::forget('tags.all');

        return response()->json(null, 204);
    }
}
