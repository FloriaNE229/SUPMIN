<?php

namespace App\Modules\Shared\Helpers;

use Illuminate\Http\JsonResponse;

/**
 * Helper pour standardiser les réponses API selon le contrat système
 */
class ApiResponse
{
    /**
     * Retourner une réponse succès
     */
    public static function success($data = null, string $message = 'Opération réussie', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => null,
            'meta' => null,
        ];

        return response()->json($response, $status);
    }

    /**
     * Retourner une réponse erreur
     */
    public static function error(string $message = 'Erreur', int $status = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
            'meta' => null,
        ];

        return response()->json($response, $status);
    }

    /**
     * Retourner une réponse avec métadonnées (pagination, etc.)
     */
    public static function successWithMeta($data, string $message, array $meta, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => null,
            'meta' => $meta,
        ];

        return response()->json($response, $status);
    }

    

    /**
     * Retourner une réponse validation error
     */
    public static function validationError(string $message = 'Erreur de validation', $errors = null): JsonResponse
    {
        return self::error($message, 400, $errors);
    }

    /**
     * Retourner une réponse non trouvé
     */
    public static function notFound(string $message = 'Ressource introuvable'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Retourner une réponse non autorisé
     */
    public static function unauthorized(string $message = 'Non autorisé'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Retourner une réponse accès interdit
     */
    public static function forbidden(string $message = 'Accès interdit'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Retourner une réponse erreur serveur
     */
    public static function serverError(string $message = 'Erreur serveur'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Retourner une réponse créée
     */
    public static function created($data = null, string $message = 'Ressource créée avec succès'): JsonResponse
    {
        return self::success($data, $message, 201);
    }
}
