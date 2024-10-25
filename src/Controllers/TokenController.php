<?php

namespace Alexanderurakov\RoketBookLibraryApi\Controllers;

use Alexanderurakov\RoketBookLibraryApi\Models\Token;
use Exception;

/**
 * @OA\PathItem(
 *     path="/tokens"
 * )
 */
/**
 * @OA\Tag(
 *     name="tokens",
 *     description="Операции с токенами"
 * )
 */
class TokenController
{
    private Token $tokenModel;

    public function __construct()
    {
        $this->tokenModel = new Token();
    }

    /**
     * Возвращает стандартный JSON-ответ с заданным статусом и сообщением.
     */
    private function createResponse(string $status, string $message, int $httpCode = 200): array
    {
        http_response_code($httpCode);
        return ['status' => $status, 'message' => $message];
    }

    /**
     * @OA\Post(
     *     path="/tokens/save",
     *     tags={"tokens"},
     *     summary="Сохранение токена для пользователя",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "token"},
     *             @OA\Property(property="user_id", type="integer", description="ID пользователя, которому принадлежит токен"),
     *             @OA\Property(property="token", type="string", description="JWT токен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Токен успешно сохранен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Token saved successfully.")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Не удалось сохранить токен")
     * )
     */
    public function saveToken(array $data): array
    {
        try {
            if (!isset($data['user_id'], $data['token'])) {
                return $this->createResponse('error', 'User ID and token are required.', 400);
            }

            $userId = (int)$data['user_id'];
            $token = $data['token'];

            if (empty($userId) || empty($token)) {
                return $this->createResponse('error', 'User ID and token cannot be empty.', 400);
            }

            // Генерируем дату истечения токена (например, через 1 час)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            if ($this->tokenModel->saveToken($userId, $token, $expiresAt)) {
                return $this->createResponse('success', 'Token saved successfully.');
            }

            return $this->createResponse('error', 'Failed to save token.', 500);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->createResponse('error', 'Internal Server Error.', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/tokens/{userId}",
     *     tags={"tokens"},
     *     summary="Удаление токена пользователя",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="ID пользователя, чей токен нужно удалить",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Токен успешно удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Token deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Не удалось удалить токен")
     * )
     */
    public function deleteToken(int $userId): array
    {
        if ($userId <= 0) {
            return $this->createResponse('error', 'Invalid user ID.', 400);
        }

        try {
            if ($this->tokenModel->deleteToken($userId)) {
                return $this->createResponse('success', 'Token deleted successfully.');
            }

            return $this->createResponse('error', 'Failed to delete token.', 500);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->createResponse('error', 'Internal Server Error.', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/tokens/{userId}",
     *     tags={"tokens"},
     *     summary="Проверка существования токена для пользователя",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="ID пользователя для проверки токена",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Токен существует",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Token exists.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Токен не существует",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Token does not exist.")
     *         )
     *     )
     * )
     */
    public function tokenExists(int $userId): array
    {
        if ($userId <= 0) {
            return $this->createResponse('error', 'Invalid user ID.', 400);
        }

        try {
            if ($this->tokenModel->tokenExists($userId)) {
                return $this->createResponse('success', 'Token exists.');
            }

            return $this->createResponse('error', 'Token does not exist.', 404);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->createResponse('error', 'Internal Server Error.', 500);
        }
    }
}
