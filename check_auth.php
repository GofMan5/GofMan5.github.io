<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

// Подключаем файл аутентификации
require_once 'auth.php';

// Запускаем сессию
session_start();

// Проверяем наличие токена в сессии
$isAuthenticated = isset($_SESSION['auth_token']) && checkSession($_SESSION['auth_token']);

// Отправляем JSON ответ
header('Content-Type: application/json');
echo json_encode([
    'authenticated' => $isAuthenticated,
    'username' => $isAuthenticated ? $_SESSION['username'] : null,
    'display_name' => $isAuthenticated ? $_SESSION['display_name'] : null
]); 