<?php

namespace App\Http\Controllers\Api\Documentation;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Translation Management Service API",
 *     description="API Documentation for Translation Management Service",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 * @OA\Tag(
 *     name="Languages",
 *     description="Language management endpoints"
 * )
 * @OA\Tag(
 *     name="Translations",
 *     description="Translation management endpoints"
 * )
 * @OA\Tag(
 *     name="Tags",
 *     description="Tag management endpoints"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class ApiDocController extends Controller
{
}
