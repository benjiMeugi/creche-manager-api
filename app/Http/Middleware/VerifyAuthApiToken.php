<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class VerifyAuthApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => "Token d'authentification manquant.",
                'body' => [
                    'error_message' => "Token d'authentification manquant.",
                    'errors' => null,
                    'response_data' => null,
                ],
            ], 401);
        }

        try {
            $baseUrl = config('services.auth_api.base_url', env('AUTH_API_BASE_URL', 'http://authentication-api'));
            $baseUrl = rtrim((string) $baseUrl, '/');

            $response = Http::withToken($token)
                ->acceptJson()
                ->get($baseUrl . '/api/validate-token');

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'code' => 401,
                    'message' => 'Token invalide ou expiré.',
                    'body' => [
                        'error_message' => 'Token invalide ou expiré.',
                        'errors' => [
                            'api_response_status' => $response->status(),
                            'api_response_body' => $response->json(),
                        ],
                        'response_data' => null,
                    ],
                ], 401);
            }

            $userData = $response->json();

            if (is_array($userData) && isset($userData['body']['response_data'])) {
                $userData = $userData['body']['response_data'];
            } elseif (is_array($userData) && isset($userData['data'])) {
                $userData = $userData['data'];
            }

            $request->merge(['auth_user' => $userData]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'code' => 503,
                'message' => "Service d'authentification injoignable.",
                'body' => [
                    'error_message' => $e->getMessage(),
                    'errors' => null,
                    'response_data' => null,
                ],
            ], 503);
        }

        return $next($request);
    }
}
