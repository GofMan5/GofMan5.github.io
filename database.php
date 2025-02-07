<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            error_log("=== Попытка подключения к БД ===");
            error_log("Host: localhost");
            error_log("Database: ems");
            
            $this->conn = new PDO(
                "mysql:host=localhost;dbname=ems;charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
            error_log("Подключение к БД успешно установлено");
        } catch(PDOException $e) {
            error_log("Ошибка подключения к БД: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Проверяем подключение к БД при инициализации файла
try {
    $db = Database::getInstance()->getConnection();
    
    // Создаем базу данных если её нет
    $db->exec("CREATE DATABASE IF NOT EXISTS ems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE ems");
    
    // SQL для создания таблицы администраторов
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS administrators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('HEAD PM', 'HEAD PSID', 'HEAD DI', 'HAD') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($createTableSQL);
    error_log("Таблица administrators успешно создана или уже существует");
    
    // Создаем таблицу для загрузок на imgur
    $db->exec("CREATE TABLE IF NOT EXISTS `imgur_uploads` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `image_url` VARCHAR(255) NOT NULL,
        `delete_hash` VARCHAR(255) NOT NULL,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `user_ip` VARCHAR(45),
        `user_agent` VARCHAR(255),
        INDEX `idx_uploaded_at` (`uploaded_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    error_log("Таблица imgur_uploads успешно создана или уже существует");
    
} catch(PDOException $e) {
    error_log("Ошибка инициализации БД: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    die("Ошибка создания таблицы: " . $e->getMessage());
} 