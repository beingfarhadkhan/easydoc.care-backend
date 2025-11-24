<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Handle unauthenticated exceptions for Sanctum / API requests.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // 👇 This ensures Sanctum returns JSON instead of redirecting to /login
        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated or token expired.',
            ], 401);
        }

        // Fallback JSON for non-API requests
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated. Login required.',
        ], 401);
    }
}