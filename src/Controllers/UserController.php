<?php

namespace Alexanderurakov\RoketBookLibraryApi\Controllers;

use Alexanderurakov\RoketBookLibraryApi\Models\Token;
use Alexanderurakov\RoketBookLibraryApi\Models\User;
use Firebase\JWT\JWT;

/**
 * @OA\PathItem(
 *     path="/users"
 * )
 */
/**
 * @OA\Tag(
 *     name="users",
 *     description="Операции с пользователями"
 * )
 */
class UserController
{
    private User $userModel;
    private Token $tokenModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->tokenModel = new Token();
    }

    /**
     * Возвращает стандартный JSON-ответ с заданным статусом и сообщением.
     */
    private function createResponse(string $message, string $status = 'success', int $httpCode = 200): string
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        return json_encode(['status' => $status, 'message' => $message]);
    }

    /**
     * @OA\Post(
     *     path="/users/register",
     *     tags={"users"},
     *     summary="Регистрация пользователя",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "password", "password_confirm"},
     *             @OA\Property(property="username", type="string", description="Имя пользователя"),
     *             @OA\Property(property="password", type="string", description="Пароль"),
     *             @OA\Property(property="password_confirm", type="string", description="Подтверждение пароля")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Пользователь успешно зарегистрирован",
     *     ),
     *     @OA\Response(response=400, description="Пароли не совпадают"),
     *     @OA\Response(response=409, description="Пользователь уже существует"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     */
    public function register(array $data): string
    {
        if (!isset($data['username'], $data['password'], $data['password_confirm'])) {
            return $this->createResponse("Username, password, and password confirmation are required.", 'error', 400);
        }

        $username = $data['username'];
        $password = $data['password'];
        $passwordConfirm = $data['password_confirm'];

        if ($password !== $passwordConfirm) {
            return $this->createResponse("Passwords do not match.", 'error', 400);
        }

        try {
            $this->userModel->beginTransaction();

            if ($this->userModel->create($username, $password)) {
                $userId = $this->userModel->getIdByUsername($username);
                $token = $this->generateJWT($userId);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);
                $this->tokenModel->saveToken($userId, $token, $expiresAt);

                $this->userModel->commit();
                return $this->createResponse("User created successfully.", 'success', 201);
            }

            $this->userModel->rollback();
            return $this->createResponse("User already exists.", 'error', 409);
        } catch (\Exception $e) {
            $this->userModel->rollback();
            return $this->createResponse("Registration failed: " . $e->getMessage(), 'error', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/users/login",
     *     tags={"users"},
     *     summary="Аутентификация пользователя",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "password"},
     *             @OA\Property(property="username", type="string", description="Имя пользователя"),
     *             @OA\Property(property="password", type="string", description="Пароль")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Аутентификация успешна",
     *     ),
     *     @OA\Response(response=401, description="Неверные учетные данные"),
     *     @OA\Response(response=400, description="Ошибка входных данных")
     * )
     */
    public function login(array $data): string
    {
        if (!isset($data['username'], $data['password'])) {
            return $this->createResponse("Username and password are required.", 'error', 400);
        }

        $username = $data['username'];
        $password = $data['password'];
        $userId = $this->userModel->login($username, $password);

        if ($userId) {
            $token = $this->generateJWT($userId);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $this->tokenModel->saveToken($userId, $token, $expiresAt);
            return $this->createResponse("User logged in successfully.", 'success', 200);
        }

        return $this->createResponse("Invalid credentials.", 'error', 401);
    }

    /**
     * @OA\Get(
     *     path="/users",
     *     tags={"users"},
     *     summary="Получение списка всех пользователей",
     *     @OA\Response(
     *         response=200,
     *         description="Список пользователей",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *     ),
     *     @OA\Response(response=500, description="Внутренняя ошибка сервера")
     * )
     */
    public function getAllUsers()
    {
        try {
            return $this->userModel->getAllUsers();
        } catch (\Exception $e) {
            return $this->createResponse("Internal Server Error: " . $e->getMessage(), 'error', 500);
        }
    }

    /**
     * Создание JWT токена.
     */
    private function generateJWT(int $userId): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + ($_ENV['JWT_EXPIRY_TIME'] ?? 3600); // Время истечения по умолчанию - 1 час
        $payload = [
            'iss' => $_ENV['APP_URL'], // Источник токена
            'sub' => $userId,          // ID пользователя
            'iat' => $issuedAt,        // Время создания токена
            'exp' => $expirationTime   // Время истечения токена
        ];

        if (!isset($_ENV['JWT_SECRET'])) {
            throw new \Exception("JWT secret not set in environment.");
        }

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }
}