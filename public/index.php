<?php

require '../vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

use Alexanderurakov\RoketBookLibraryApi\Controllers\{
    TokenController, UserController, BookController
};

// Централизованная маршрутизация
$routes = [
    'GET' => [
        '/books/user/{id}' => [BookController::class, 'getBooksByUser'],
        '/books/{id}' => [BookController::class, 'getBookById'],
        '/users' => [UserController::class, 'getAllUsers'],
        '/tokens/{id}' => [TokenController::class, 'tokenExists'],
    ],
    'POST' => [
        '/books' => [BookController::class, 'create'],
        '/books/restore' => [BookController::class, 'restore'],
        '/users/register' => [UserController::class, 'register'],
        '/users/login' => [UserController::class, 'login'],
        '/tokens' => [TokenController::class, 'saveToken'],
    ],
    'DELETE' => [
        '/books/{id}' => [BookController::class, 'delete'],
        '/tokens/{id}' => [TokenController::class, 'deleteToken']
    ]
];

// Получаем метод запроса и URI
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Функция для разбора маршрутов и их соответствия
function matchRoute($method, $uri, $routes)
{
    foreach ($routes[$method] as $route => $controllerAction) {
        // Подставляем параметры в маршрут
        $pattern = preg_replace('/\{[a-z_]+\}/', '(\d+)', $route);
        if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
            array_shift($matches); // Убираем полное совпадение
            return [$controllerAction, $matches]; // Возвращаем контроллер и параметры
        }
    }
    return null;
}

// Обрабатываем маршруты
$route = matchRoute($requestMethod, $requestUri, $routes);

if ($route) {
    [$controllerAction, $params] = $route;
    [$controllerClass, $action] = $controllerAction;
    $controller = new $controllerClass();

    // Получаем данные из запроса, если есть
    $data = json_decode(file_get_contents("php://input"), true) ?? [];

    // Передаем параметры и данные как отдельные аргументы
    $response = $controller->$action(...array_merge($params, [$data]));

    // Отправляем JSON-ответ
    echo json_encode($response);
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Route not found']);
}