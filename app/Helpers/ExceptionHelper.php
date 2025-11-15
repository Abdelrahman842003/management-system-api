<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ExceptionHelper
{
    /**
     * Generate a standardized error response for API.
     *
     * @param  int  $statusCode
     * @param  string  $message
     * @param  array|string|null  $errors
     * @return JsonResponse
     */
    public static function apiErrorResponse(
        int $statusCode,
        string $message,
        $errors = null
    ): JsonResponse {
        $response = [
            'status' => $statusCode,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Handle ModelNotFoundException and return formatted response.
     *
     * @param  string  $modelName
     * @return JsonResponse
     */
    public static function modelNotFoundResponse(string $modelName): JsonResponse
    {
        return self::apiErrorResponse(
            404,
            "{$modelName} not found",
            ['resource' => "The requested {$modelName} could not be found."]
        );
    }

    /**
     * Handle general NotFoundHttpException and return formatted response.
     *
     * @return JsonResponse
     */
    public static function resourceNotFoundResponse(): JsonResponse
    {
        return self::apiErrorResponse(
            404,
            'Resource not found',
            ['resource' => 'The requested resource could not be found.']
        );
    }

    /**
     * Handle validation errors.
     *
     * @param  array  $errors
     * @return JsonResponse
     */
    public static function validationErrorResponse(array $errors): JsonResponse
    {
        return self::apiErrorResponse(
            422,
            'Validation failed',
            $errors
        );
    }

    /**
     * Handle unauthorized access.
     *
     * @param  string|null  $message
     * @return JsonResponse
     */
    public static function unauthorizedResponse(?string $message = null): JsonResponse
    {
        return self::apiErrorResponse(
            401,
            $message ?? 'Unauthorized',
            ['auth' => 'You are not authorized to access this resource.']
        );
    }

    /**
     * Handle forbidden access.
     *
     * @param  string|null  $message
     * @return JsonResponse
     */
    public static function forbiddenResponse(?string $message = null): JsonResponse
    {
        return self::apiErrorResponse(
            403,
            $message ?? 'Forbidden',
            ['auth' => 'You do not have permission to perform this action.']
        );
    }

    /**
     * Handle server errors.
     *
     * @param  string|null  $message
     * @return JsonResponse
     */
    public static function serverErrorResponse(?string $message = null): JsonResponse
    {
        return self::apiErrorResponse(
            500,
            $message ?? 'Internal Server Error',
            ['server' => 'An unexpected error occurred. Please try again later.']
        );
    }

    /**
     * Throw authorization exception with custom message.
     *
     * @param  string  $message
     * @return never
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function throwUnauthorized(string $message): never
    {
        throw new \Illuminate\Auth\Access\AuthorizationException($message);
    }

    /**
     * Check authorization and throw exception if failed.
     *
     * @param  bool  $condition
     * @param  string  $message
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function authorize(bool $condition, string $message): void
    {
        if (!$condition) {
            self::throwUnauthorized($message);
        }
    }
}
