<?php

if (! class_exists(\BenjiMeugi\Parser::class) && is_dir(__DIR__ . '/../packages/benjimeugi/src')) {
    spl_autoload_register(function (string $class): void {
        $prefix = 'BenjiMeugi\\';
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = __DIR__ . '/../packages/benjimeugi/src/' . $relative . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        }
    });
}

use App\Http\Middleware\CheckAbility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ability' => CheckAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Intercepte et formate toutes les erreurs de l'API au format JSON uniformisé
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                try {
                    Log::error('Api error: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
                } catch (\Throwable $logException) {
                    // Ignorer les erreurs de logging
                }

                // 1. Route [login] non définie
                if ($e instanceof RouteNotFoundException && str_contains($e->getMessage(), 'login')) {
                    return response()->json([
                        'success' => false,
                        'code' => 401,
                        'message' => 'Non authentifié.',
                        'body' => [
                            'error_message' => 'Route [login] not defined or token missing.',
                            'errors' => null,
                            'response_data' => null,
                        ],
                    ], 401);
                }

                // 2. Non authentifié
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'code' => 401,
                        'message' => 'Non authentifié. Veuillez fournir un token valide.',
                        'body' => [
                            'error_message' => $e->getMessage(),
                            'errors' => null,
                            'response_data' => null,
                        ],
                    ], 401);
                }

                // 3. Non autorisé
                if (
                    $e instanceof AuthorizationException
                    || $e instanceof AccessDeniedHttpException
                    || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() === 403)
                ) {
                    return response()->json([
                        'success' => false,
                        'code' => 403,
                        'message' => "Action non autorisée. Vous n'avez pas la permission requise.",
                        'body' => [
                            'error_message' => $e->getMessage(),
                            'errors' => null,
                            'response_data' => null,
                        ],
                    ], 403);
                }

                // 4. Ressource introuvable
                if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'code' => 404,
                        'message' => 'Ressource introuvable.',
                        'body' => [
                            'error_message' => $e->getMessage(),
                            'errors' => null,
                            'response_data' => null,
                        ],
                    ], 404);
                }

                // 5. Méthode non autorisée
                if ($e instanceof MethodNotAllowedHttpException) {
                    return response()->json([
                        'success' => false,
                        'code' => 405,
                        'message' => 'Méthode non autorisée.',
                        'body' => [
                            'error_message' => $e->getMessage(),
                            'errors' => null,
                            'response_data' => null,
                        ],
                    ], 405);
                }

                // 6. Erreur interne du serveur
                $debug = false;
                try {
                    $debug = config('app.debug');
                } catch (\Throwable $configException) {
                    $debug = false;
                }

                return response()->json([
                    'success' => false,
                    'code' => 500,
                    'message' => 'Erreur interne du serveur.',
                    'body' => [
                        'error_message' => $debug ? $e->getMessage() : 'Une erreur est survenue.',
                        'errors' => null,
                        'response_data' => null,
                    ],
                ], 500);
            }
        });
    })->create();
