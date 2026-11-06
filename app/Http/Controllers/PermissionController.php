<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Renvoie les droits d'accès de l'employé connecté depuis auth_user injecté par VerifyAuthApiToken.
     */
    public function show(Request $request)
    {
        $authUser = $request->input('auth_user');

        if (is_object($authUser)) {
            $authUser = (array) $authUser;
        }

        $roles = $authUser['roles'] ?? ($authUser['role'] ?? []);
        if (is_string($roles)) {
            $roles = [$roles];
        }
        if (! is_array($roles)) {
            $roles = [];
        }

        $permissions = $authUser['permissions'] ?? $authUser['abilities'] ?? [];
        if (! is_array($permissions)) {
            $permissions = [];
        }

        $roles = array_values(array_unique($roles));
        $permissions = array_values(array_unique($permissions));

        return $this->respondOk([
            'user' => $authUser,
            'roles' => $roles,
            'permissions' => $permissions,
            'abilities' => $permissions,
        ]);
    }

    public function me(Request $request)
    {
        return $this->show($request);
    }
}
