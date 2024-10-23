<?php
// CORS заголовки
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Проверяем запрос OPTIONS для предзапроса CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Заголовки для предзапроса CORS
    header("HTTP/1.1 200 OK");
    exit();
}

// Основной код
require_once '../vendor/autoload.php';

/**
 * @OA\Info(
 *     title="Book Library API",
 *     version="1.0.0",
 *     description="API для управления библиотекой книг."
 * )
 */

// Генерация OpenAPI спецификации
$openapi = \OpenApi\Generator::scan(['../src']);
header("Content-Type: application/json; charset=UTF-8");
echo $openapi->toJson();