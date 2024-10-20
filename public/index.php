<?php

require '../vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

use Alexanderurakov\RoketBookLibraryApi\Controllers\UserController;

$requestMethod = $_SERVER["REQUEST_METHOD"];
$requestUri = $_SERVER["REQUEST_URI"];

$userController = new UserController();


// Создание нового пользователя
if ($requestMethod === 'POST' && $requestUri === '/users/register') {
    $data = json_decode(file_get_contents("php://input"), true);
    echo json_encode($userController->register($data));
}

// Аутентификация пользователя
if ($requestMethod === 'POST' && $requestUri === '/users/login') {
    $data = json_decode(file_get_contents("php://input"), true);
    echo json_encode($userController->login($data));
}

// Получение списка участников
if ($requestMethod === 'GET' && $requestUri === '/users') {
    echo json_encode($userController->getAllUsers());
}
