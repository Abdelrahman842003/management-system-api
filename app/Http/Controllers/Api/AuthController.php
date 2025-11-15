<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ExceptionHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user with specified role.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return $this->successResponse(
                [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                ],
                'User registered successfully',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return ExceptionHelper::serverErrorResponse('Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Login user and return access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->successResponse(
            [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            'Login successful',
            Response::HTTP_OK
        );
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(
            null,
            'Logged out successfully',
            Response::HTTP_OK
        );
    }

    /**
     * Get authenticated user with roles and permissions.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request->user());

        return $this->successResponse(
            new UserResource($user),
            'User retrieved successfully'
        );
    }
}
