<?php

namespace Alexanderurakov\RoketBookLibraryApi\Controllers;

use Alexanderurakov\RoketBookLibraryApi\Models\User;
use Firebase\JWT\JWT;

class UserController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Возвращает JSON-ответ.
     */
    private function jsonResponse(array $data): string
    {
        return json_encode($data);
    }

    public function register(array $data): string
    {
        $username = $data['username'];
        $password = $data['password'];
        $passwordConfirm = $data['password_confirm'];

        if ($password !== $passwordConfirm) {
            http_response_code(400);
            return $this->jsonResponse(["message" => "Passwords do not match."]);
        }

        if ($this->userModel->create($username, $password)) {
            $userId = $this->userModel->getIdByUsername($username);
            $token = $this->generateJWT($userId);

            return $this->jsonResponse(["message" => "User created successfully.", "token" => $token]);
        }

        http_response_code(409);
        return $this->jsonResponse(["message" => "User already exists."]);
    }

    public function login(array $data): string
    {
        $username = $data['username'];
        $password = $data['password'];
        $userId = $this->userModel->login($username, $password);

        if ($userId) {
            $token = $this->generateJWT($userId);
            return $this->jsonResponse(["message" => "User logged in successfully.", "token" => $token]);
        }

        http_response_code(401);
        return $this->jsonResponse(["message" => "Invalid credentials."]);
    }

    public function getAllUsers(): array
    {
        return $this->userModel->getAllUsers();
    }

    /**
     * Создание JWT токена.
     * Нужно заменить localhost:8000 на свой домен
     */
    private function generateJWT(int $userId): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // 1 час
        $payload = [
            'iss' => 'localhost:8000',
            'sub' => $userId,
            'iat' => $issuedAt,
            'exp' => $expirationTime
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }
}
