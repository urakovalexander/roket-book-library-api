<?php

namespace Alexanderurakov\RoketBookLibraryApi\Models;

use Alexanderurakov\RoketBookLibraryApi\Config\Database;
use PDO;
use PDOException;

class User
{
    private PDO $conn;
    private string $table_name = "users";

    public function __construct()
    {
        $this->conn = Database::getInstance();
    }

    /**
     * Проверяет, существует ли пользователь с указанным именем.
     *
     * @param string $username Имя пользователя.
     * @return bool Возвращает true, если пользователь существует, иначе false.
     */
    public function userExists(string $username): bool
    {
        $query = "SELECT id FROM $this->table_name WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Создает нового пользователя.
     *
     * @param string $username Имя пользователя.
     * @param string $password Пароль пользователя.
     * @return bool Возвращает true, если пользователь успешно создан, иначе false.
     */
    public function create(string $username, string $password): bool
    {
        if ($this->userExists($username)) {
            return false; // Пользователь уже существует
        }

        $query = "INSERT INTO $this->table_name (username, password_hash) VALUES (:username, :password)";
        $stmt = $this->conn->prepare($query);

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password_hash);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Аутентифицирует пользователя.
     *
     * @param string $username Имя пользователя.
     * @param string $password Пароль пользователя.
     * @return int|false Возвращает ID пользователя, если аутентификация успешна, иначе false.
     */
    public function login(string $username, string $password): false|int
    {
        $query = "SELECT id, password_hash FROM $this->table_name WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password_hash'])) {
                return $row['id'];
            }
        }
        return false;
    }

    /**
     * Возвращает всех пользователей.
     *
     * @return array Массив пользователей с полями id и username.
     */
    public function getAllUsers(): array
    {
        $query = "SELECT id, username FROM $this->table_name";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Возвращает ID пользователя по имени.
     *
     * @param string $username Имя пользователя.
     * @return int|false ID пользователя или false, если пользователь не найден.
     */
    public function getIdByUsername(string $username): false|int
    {
        $query = "SELECT id FROM $this->table_name WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : false;
    }
}
