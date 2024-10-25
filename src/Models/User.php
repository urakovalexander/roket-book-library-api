<?php

namespace Alexanderurakov\RoketBookLibraryApi\Models;

use Alexanderurakov\RoketBookLibraryApi\Config\Database;
use PDO;
use PDOException;

/**
 * @OA\Schema(
 *   schema="User",
 *   type="object",
 *   description="Пользователь",
 *   @OA\Property(property="id", type="integer", description="ID пользователя"),
 *   @OA\Property(property="username", type="string", description="Имя пользователя"),
 *   @OA\Property(property="password_hash", type="string", description="Хеш пароля"),
 *   @OA\Property(property="created_at", type="string", format="date-time", description="Дата создания"),
 * )
 */
class User
{
    private PDO $conn;
    private string $table_name = "users";

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
     * Начинает транзакцию.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->conn->beginTransaction();
    }

    /**
     * Подтверждает транзакцию.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->conn->commit();
    }

    /**
     * Откатывает транзакцию.
     *
     * @return void
     */
    public function rollback(): void
    {
        $this->conn->rollBack();
    }

    /**
     * Проверяет, существует ли пользователь с указанным именем.
     *
     * @param string $username Имя пользователя.
     * @return array Результат проверки с сообщением.
     */
    public function userExists(string $username): array
    {
        if (empty($username)) {
            return $this->createResponse(false, 'Username is required.');
        }

        $query = "SELECT id FROM $this->table_name WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':username', $username);

        try {
            $stmt->execute();
            return $stmt->fetch() ? $this->createResponse(true, 'User exists.') : $this->createResponse(false, 'User does not exist.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Создает нового пользователя.
     *
     * @param string $username Имя пользователя.
     * @param string $password Пароль пользователя.
     * @return array Результат операции с сообщением.
     */
    public function create(string $username, string $password): array
    {
        if (empty($username) || empty($password)) {
            return $this->createResponse(false, 'Username and password are required.');
        }

        $query = "INSERT INTO $this->table_name (username, password_hash) VALUES (:username, :password)";
        $stmt = $this->conn->prepare($query);

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $password_hash);

        try {
            if ($stmt->execute()) {
                return $this->createResponse(true, 'User created successfully.');
            }
            return $this->createResponse(false, 'Failed to create user.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return $this->createResponse(false, 'User already exists.');
            }
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Аутентифицирует пользователя.
     *
     * @param string $username Имя пользователя.
     * @param string $password Пароль пользователя.
     * @return array Результат аутентификации с сообщением.
     */
    public function login(string $username, string $password): array
    {
        if (empty($username) || empty($password)) {
            return $this->createResponse(false, 'Username and password are required.');
        }

        $query = "SELECT id, password_hash FROM $this->table_name WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':username', $username);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && password_verify($password, $row['password_hash'])) {
                return ['success' => true, 'user_id' => $row['id']];
            }
            return $this->createResponse(false, 'Invalid username or password.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Возвращает список всех пользователей.
     *
     * @return array Массив пользователей или сообщение об ошибке.
     */
    public function getAllUsers(): array
    {
        $query = "SELECT id, username FROM $this->table_name";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: $this->createResponse(false, 'No users found.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Возвращает ID пользователя по его имени.
     *
     * @param string $username Имя пользователя.
     * @return array Результат поиска с ID пользователя или сообщением об ошибке.
     */
    public function getIdByUsername(string $username): array
    {
        if (empty($username)) {
            return $this->createResponse(false, 'Username is required.');
        }

        $query = "SELECT id FROM $this->table_name WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':username', $username);

        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? ['success' => true, 'user_id' => $result['id']] : $this->createResponse(false, 'User not found.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }
}