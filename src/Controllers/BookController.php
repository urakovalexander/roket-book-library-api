<?php

namespace Alexanderurakov\RoketBookLibraryApi\Controllers;

use Alexanderurakov\RoketBookLibraryApi\Models\Book;

/**
 * @OA\PathItem(
 *     path="/books"
 * )
 */

/**
 * @OA\Tag(
 *     name="books",
 *     description="Операции с книгами"
 * )
 */
class BookController
{
    private Book $bookModel;

    public function __construct()
    {
        $this->bookModel = new Book();
    }

    /**
     * Возвращает стандартный JSON-ответ с заданным статусом и сообщением.
     */
    private function createResponse(string $message, string $status = 'success', int $httpCode = 200): array
    {
        http_response_code($httpCode);
        return ['status' => $status, 'message' => $message];
    }

    /**
     * @OA\Post(
     *     path="/books",
     *     tags={"books"},
     *     summary="Создание новой книги",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "title", "content"},
     *             @OA\Property(property="user_id", type="integer", description="ID пользователя"),
     *             @OA\Property(property="title", type="string", description="Название книги"),
     *             @OA\Property(property="content", type="string", description="Содержимое книги")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Книга успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book created successfully.")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Ошибка при создании книги"),
     *     @OA\Response(response=500, description="Внутренняя ошибка сервера")
     * )
     */
    public function create(array $data): array
    {
        // Проверка наличия всех необходимых полей
        if (!isset($data['user_id'], $data['title'], $data['content'])) {
            return $this->createResponse('Missing required fields.', 'error', 400);
        }

        $userId = $data['user_id'];
        $title = $data['title'];
        $content = $data['content'];

        try {
            if ($this->bookModel->create($userId, $title, $content)) {
                return $this->createResponse('Book created successfully.', 'success', 201);
            }
            return $this->createResponse('Failed to create book.', 'error', 400);
        } catch (\Exception $e) {
            return $this->createResponse('Server error: ' . $e->getMessage(), 'error', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/books/user/{id}",
     *     tags={"books"},
     *     summary="Получение списка книг пользователя",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID пользователя"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список книг",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Book"))
     *     ),
     *     @OA\Response(response=404, description="Пользователь не найден"),
     *     @OA\Response(response=500, description="Внутренняя ошибка сервера")
     * )
     */
    public function getBooksByUser(int $userId): array
    {
        try {
            $books = $this->bookModel->getBooksByUser($userId);
            if (empty($books)) {
                return $this->createResponse('No books found for this user.', 'error', 404);
            }
            return $books;
        } catch (\Exception $e) {
            return $this->createResponse('Server error: ' . $e->getMessage(), 'error', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/books/{id}",
     *     tags={"books"},
     *     summary="Получить книгу по ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID книги"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Информация о книге",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Моя книга"),
     *             @OA\Property(property="content", type="string", example="Текст книги...")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Книга не найдена"),
     *     @OA\Response(response=500, description="Внутренняя ошибка сервера")
     * )
     */
    public function getBookById(int $bookId): array|false
    {
        try {
            $book = $this->bookModel->getBookById($bookId);
            if (!$book) {
                return $this->createResponse('Book not found.', 'error', 404);
            }
            return $book;
        } catch (\Exception $e) {
            return $this->createResponse('Server error: ' . $e->getMessage(), 'error', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/books/{id}",
     *     tags={"books"},
     *     summary="Обновление книги",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID книги"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "content"},
     *             @OA\Property(property="title", type="string", description="Название книги"),
     *             @OA\Property(property="content", type="string", description="Содержимое книги")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Книга успешно обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book updated successfully.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Книга не найдена"),
     *     @OA\Response(response=500, description="Внутренняя ошибка сервера")
     * )
     */
    public function update(int $bookId, array $data): array
    {
        if (!isset($data['title'], $data['content'])) {
            return $this->createResponse('Missing required fields.', 'error', 400);
        }

        $title = $data['title'];
        $content = $data['content'];

        try {
            if ($this->bookModel->update($bookId, $title, $content)) {
                return $this->createResponse('Book updated successfully.');
            }
            return $this->createResponse('Failed to update book.', 'error', 404);
        } catch (\Exception $e) {
            return $this->createResponse('Server error: ' . $e->getMessage(), 'error', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/books/{id}",
     *     tags={"books"},
     *     summary="Удаление книги",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID книги"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Книга успешно удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Book deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Книга не найдена"),
     *     @OA\Response(response=500, description="Внутренняя ошибка сервера")
     * )
     */
    public function delete(int $bookId): array
    {
        try {
            if ($this->bookModel->delete($bookId)) {
                return $this->createResponse('Book deleted successfully.');
            }
            return $this->createResponse('Failed to delete book.', 'error', 404);
        } catch (\Exception $e) {
            return $this->createResponse('Server error: ' . $e->getMessage(), 'error', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/books/restore",
     *     tags={"books"},
     *     summary="Восстановление удаленной книги",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"book_id"},
     *             @OA\Property(property="book_id", type="integer", description="ID книги")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Книга успешно восстановлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book restored successfully.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Книга не найдена"),
     *     @OA\Response(response=500, description="Внутренняя ошибка сервера")
     * )
     */
    public function restore(int $bookId): array
    {
        try {
            if ($this->bookModel->restore($bookId)) {
                return $this->createResponse('Book restored successfully.');
            }
            return $this->createResponse('Failed to restore book.', 'error', 404);
        } catch (\Exception $e) {
            return $this->createResponse('Server error: ' . $e->getMessage(), 'error', 500);
        }
    }
}