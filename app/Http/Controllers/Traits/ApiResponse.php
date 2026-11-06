<?php

namespace App\Http\Controllers\Traits;

use Exception;
use Illuminate\Support\Facades\Log;

trait ApiResponse
{
    private function response($success, $code, $error_message = null, $errors = null, $response_data = null, $message = null)
    {
        return response()->json([
            'success' => $success,
            'code' => $code,
            'message' => $message,
            'body' => [
                'error_message' => $error_message,
                'errors' => $errors,
                'response_data' => $response_data,
            ],
        ], $code);
    }

    public function respondOk($data, $message = null)
    {
        return $this->response(true, 200, null, null, $data, $message);
    }

    public function respondCreated($data, $message = 'Ressource créée avec succès.')
    {
        return $this->response(true, 201, null, null, $data, $message);
    }

    public function respondUnauthorized($message = 'Non authentifié.')
    {
        return $this->response(false, 401, $message, null, null, $message);
    }

    public function respondForbidden($message = 'Action non autorisée.')
    {
        return $this->response(false, 403, $message, null, null, $message);
    }

    protected function respondError(Exception $e, $errors = null, $code = 500)
    {
        Log::error('Api Custom respondError: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        return $this->response(false, $code, $e->getMessage(), $errors);
    }

    protected function respondBadRequest($errors, $message = 'Données invalides.')
    {
        return $this->response(false, 422, $message, $errors);
    }

    protected function respondNotFound($id = null, $message = 'Ressource introuvable.')
    {
        $errorMsg = $id ? "Ressource introuvable pour l'identifiant : {$id}" : $message;

        return $this->response(false, 404, $errorMsg);
    }

    public function respondFileDownload($file, $filename, $mimeType = 'application/pdf')
    {
        $header = [
            'Access-Control-Expose-Headers' => 'filename',
            'filename' => $filename,
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($file),
        ];

        return response($file, 200, $header);
    }
}
