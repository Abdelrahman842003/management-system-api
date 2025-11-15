<?php

use App\Helpers\ExceptionHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle ModelNotFoundException for API requests
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                $model = class_basename($e->getModel());
                return ExceptionHelper::modelNotFoundResponse($model);
            }
        });

        // Handle general NotFoundHttpException for API routes
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ExceptionHelper::resourceNotFoundResponse();
            }
        });

        // Handle AuthorizationException (403 Forbidden)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage() ?: 'This action is unauthorized';
                return ExceptionHelper::forbiddenResponse($message);
            }
        });

        // Handle AccessDeniedHttpException (403 Forbidden)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage() ?: 'Access denied';
                return ExceptionHelper::forbiddenResponse($message);
            }
        });

        // Handle ValidationException (422 Unprocessable Entity)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ExceptionHelper::validationErrorResponse($e->errors());
            }
        });
    })->create();
