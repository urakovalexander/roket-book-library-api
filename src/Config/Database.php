<?php

namespace Alexanderurakov\RoketBookLibraryApi\Config;

use PDO;
use PDOException;
use Dotenv\Dotenv;
use Exception;

class Database
{
    private static $instance = null;
    private ?PDO $conn = null;
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;

    /**
     * Приватный конструктор для установки соединения с базой данных.
     * Загружает переменные окружения и инициализирует параметры для подключения.
     *
     * @throws Exception Если обязательные переменные окружения отсутствуют.
     */
    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        // Загрузка и проверка обязательных переменных окружения
        $this->host = $this->getEnvVar('DB_HOST');
        $this->db_name = $this->getEnvVar('DB_NAME');
        $this->username = $this->getEnvVar('DB_USER');
        $this->password = $this->getEnvVar('DB_PASS');

        // Попытка установить соединение с базой данных
        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->db_name",
                $this->username,
                $this->password,
                [PDO::ATTR_PERSISTENT => true] // Используем постоянные соединения
            );
            // Установка режима обработки ошибок
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            // Логируем ошибку подключения
            error_log($exception->getMessage());
            throw new Exception('Не удалось подключиться к базе данных.');
        }
    }

    /**
     * Возвращает экземпляр PDO для выполнения запросов к базе данных.
     *
     * @return PDO Экземпляр PDO.
     * @throws Exception Если не удалось установить соединение.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance->conn;
    }

    /**
     * Получает переменную окружения с проверкой на наличие.
     *
     * @param string $key Название переменной окружения.
     * @return string Значение переменной окружения.
     * @throws Exception Если переменная окружения не найдена.
     */
    private function getEnvVar(string $key): string
    {
        if (!isset($_ENV[$key])) {
            throw new Exception("Переменная окружения '$key' не задана.");
        }

        return $_ENV[$key];
    }
}