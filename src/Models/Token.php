<?php

namespace Alexanderurakov\RoketBookLibraryApi\Models;

use Alexanderurakov\RoketBookLibraryApi\Config\Database;
use PDO;
use PDOException;

/**
 * @OA\Schema(
 *   schema="Token",
 *   type="object",
 *   description="Токен пользователя",
 *   @OA\Property(property="user_id", type="integer", description="ID пользователя, которому принадлежит токен"),
 *   @OA\Property(property="token", type="string", description="JWT токен"),
 *   @OA\Property(property="created_at", type="string", format="date-time", description="Дата создания токена"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", description="Дата обновления токена")
 * )
 */
class Token
{
    private PDO $conn;
    private string $table_name = "tokens";

    public function __construct()
    {
        $this->conn = Database::getInstance();
    }

    /**
     * Возвращает стандартный результат выполнения с сообщением.
     */
    private function createResponse(bool $success, string $message): array
    {
        return ['success' => $success, 'message' => $message];
    }

    /**
     * Сохраняет токен для пользователя.
     *
     * @param int $userId ID пользователя.
     * @param string $token JWT токен.
     * @param string $expiresAt Дата и время истечения токена.
     * @return array Результат операции с сообщением.
     */
    public function saveToken(int $userId, string $token, string $expiresAt): array
    {
        if (empty($userId) || empty($token) || empty($expiresAt)) {
            return $this->createResponse(false, 'User ID, token, and expiration date are required.');
        }

        // Удаление существующего токена перед сохранением нового
        $this->deleteToken($userId);

        $query = "INSERT INTO $this->table_name (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':token', $token);
        $stmt->bindValue(':expires_at', $expiresAt);

        try {
            if ($stmt->execute()) {
                return $this->createResponse(true, 'Token saved successfully.');
            }
            return $this->createResponse(false, 'Failed to save token.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Удаляет токен пользователя.
     *
     * @param int $userId ID пользователя.
     * @return array Результат операции с сообщением.
     */
    public function deleteToken(int $userId): array
    {
        if (empty($userId)) {
            return $this->createResponse(false, 'User ID is required.');
        }

        $query = "DELETE FROM $this->table_name WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $userId);

        try {
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return $this->createResponse(true, 'Token deleted successfully.');
            }
            return $this->createResponse(false, 'Token not found.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Проверяет, существует ли токен для пользователя.
     *
     * @param int $userId ID пользователя.
     * @return array Результат операции с сообщением.
     */
    public function tokenExists(int $userId): array
    {
        if (empty($userId)) {
            return $this->createResponse(false, 'User ID is required.');
        }

        $query = "SELECT token FROM $this->table_name WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $userId);

        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return $this->createResponse(true, 'Token exists.');
            }
            return $this->createResponse(false, 'Token does not exist.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }
}