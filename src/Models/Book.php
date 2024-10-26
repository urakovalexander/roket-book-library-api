<?php

namespace Alexanderurakov\RoketBookLibraryApi\Models;

use Alexanderurakov\RoketBookLibraryApi\Config\Database;
use PDO;
use PDOException;

/**
 * @OA\Schema(
 *   schema="Book",
 *   type="object",
 *   description="Книга",
 *   @OA\Property(property="id", type="integer", description="ID книги"),
 *   @OA\Property(property="user_id", type="integer", description="ID пользователя"),
 *   @OA\Property(property="title", type="string", description="Название книги"),
 *   @OA\Property(property="content", type="string", description="Содержимое книги"),
 *   @OA\Property(property="is_deleted", type="boolean", description="Книга удалена"),
 *   @OA\Property(property="created_at", type="string", format="date-time", description="Дата создания"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", description="Дата обновления"),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", description="Дата удаления")
 * )
 */
class Book
{
    private PDO $conn;
    private string $table_name = "books";

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
     * Создает новую книгу для пользователя.
     *
     * @param int $userId ID пользователя, которому принадлежит книга.
     * @param string $title Название книги.
     * @param string $content Содержимое книги.
     * @return array Результат операции с сообщением.
     */
    public function create(int $userId, string $title, string $content): array
    {
        if (empty($title) || empty($content)) {
            return $this->createResponse(false, 'Title and content cannot be empty.');
        }

        $query = "INSERT INTO $this->table_name (user_id, title, content) VALUES (:user_id, :title, :content)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':content', $content);

        try {
            if ($stmt->execute()) {
                return $this->createResponse(true, 'Book created successfully.');
            }
            return $this->createResponse(false, 'Failed to create book.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Возвращает список книг пользователя с возможностью пагинации.
     *
     * @param int $userId ID пользователя.
     * @param int $limit Количество книг на одной странице (по умолчанию 10).
     * @param int $offset Смещение для пагинации (по умолчанию 0).
     * @return array Список книг или сообщение об ошибке.
     */
    public function getBooksByUser(int $userId, int $limit = 10, int $offset = 0): array
    {
        $query = "SELECT id, title FROM $this->table_name WHERE user_id = :user_id AND is_deleted = 0 LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Возвращает информацию о книге по её ID.
     *
     * @param int $bookId ID книги.
     * @return array|false Массив с данными о книге или false, если книга не найдена.
     */
    public function getBookById(int $bookId): false|array
    {
        $query = "SELECT title, content FROM $this->table_name WHERE id = :id AND is_deleted = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $bookId);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Обновляет информацию о книге.
     *
     * @param int $bookId ID книги.
     * @param string $title Новое название книги.
     * @param string $content Новое содержимое книги.
     * @return array Результат операции с сообщением.
     */
    public function update(int $bookId, string $title, string $content): array
    {
        if (empty($title) || empty($content)) {
            return $this->createResponse(false, 'Title and content cannot be empty.');
        }

        $query = "UPDATE $this->table_name SET title = :title, content = :content WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':id', $bookId);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':content', $content);

        try {
            if ($stmt->execute()) {
                return $this->createResponse(true, 'Book updated successfully.');
            }
            return $this->createResponse(false, 'Failed to update book.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Помечает книгу как удаленную (soft delete).
     *
     * @param int $bookId ID книги.
     * @return array Результат операции с сообщением.
     */
    public function delete(int $bookId): array
    {
        return $this->updateBookStatus($bookId, true, date('Y-m-d H:i:s'));
    }

    /**
     * Восстанавливает ранее удаленную книгу.
     *
     * @param int $bookId ID книги.
     * @return array Результат операции с сообщением.
     */
    public function restore(int $bookId): array
    {
        return $this->updateBookStatus($bookId, false, null);
    }

    /**
     * Обновляет статус книги (удалена или восстановлена).
     *
     * @param int $bookId ID книги.
     * @param bool $isDeleted Флаг, указывающий, удалена ли книга.
     * @param string|null $deletedAt Время удаления книги (или NULL для восстановления).
     * @return array Результат операции с сообщением.
     */
    private function updateBookStatus(int $bookId, bool $isDeleted, ?string $deletedAt = null): array
    {
        $query = "UPDATE $this->table_name SET is_deleted = :is_deleted, deleted_at = :deleted_at WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $bookId);
        $stmt->bindValue(':is_deleted', $isDeleted, PDO::PARAM_BOOL);
        $stmt->bindValue(':deleted_at', $deletedAt);

        try {
            if ($stmt->execute()) {
                $message = $isDeleted ? 'Book deleted successfully.' : 'Book restored successfully.';
                return $this->createResponse(true, $message);
            }
            return $this->createResponse(false, 'Failed to update book status.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $this->createResponse(false, 'Database error: ' . $e->getMessage());
        }
    }
}