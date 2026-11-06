<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de vérification des habilités (permissions).
 *
 * Les autorisations des employés sont centralisées dans authentication-api.
 * Ce middleware vérifie directement dans les permissions renvoyées
 * par authentication-api via VerifyAuthApiToken (ex: registration-list, child_event-create).
 */
class CheckAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $authUser = $request->input('auth_user');

        if (is_object($authUser)) {
            $authUser = (array) $authUser;
        }

        $permissions = $authUser['permissions'] ?? $authUser['abilities'] ?? [];
        $roles = $authUser['roles'] ?? [];

        if (! is_array($permissions)) {
            $permissions = [];
        }

        if (! is_array($roles)) {
            $roles = is_string($roles) ? [$roles] : [];
        }

        if (
            in_array($ability, $permissions, true)
            || in_array('*', $permissions, true)
            || in_array('super-admin', $roles, true)
        ) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'code' => 403,
            'message' => "Action non autorisée. Vous n'avez pas la permission requise.",
            'body' => [
                'error_message' => "Permission requise manquante : {$ability}",
                'errors' => null,
                'response_data' => null,
            ],
        ], 403);
    }
}
