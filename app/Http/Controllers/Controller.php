<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    use ApiResponse;

    /**
     * Retourne l'identifiant de l'employé authentifié via authentication-api.
     */
    protected function getAuthenticatedUserId(Request $request): ?int
    {
        $authUser = $request->input('auth_user');
        if (is_object($authUser)) {
            $authUser = (array) $authUser;
        }

        $id = $authUser['id'] ?? $authUser['user_id'] ?? null;

        return $id !== null ? (int) $id : null;
    }
}
