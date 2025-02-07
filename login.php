<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Установите 1 если используете HTTPS
session_start();

// Устанавливаем заголовки для CORS
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Если это preflight запрос
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Подключаем файл аутентификации
require_once 'auth.php';

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Метод не поддерживается']));
}

// Получаем данные
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Неверные параметры']));
}

// Отладочный вывод
$inputHash = hash('sha256', $data['password']);
error_log("Input hash: " . $inputHash);
error_log("Stored hash: " . ADMIN_PASSWORD_HASH);

// Защита от брутфорса через сессию
$now = time();

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = ['count' => 0, 'last_attempt' => 0];
}

if ($_SESSION['login_attempts']['count'] >= 5 && 
    ($now - $_SESSION['login_attempts']['last_attempt']) < 300) {
    $remaining = 300 - ($now - $_SESSION['login_attempts']['last_attempt']);
    http_response_code(429);
    die(json_encode(['error' => "Слишком много попыток. Попробуйте через {$remaining} секунд"]));
}

// Если прошло 5 минут, сбрасываем счетчик
if (($now - $_SESSION['login_attempts']['last_attempt']) > 300) {
    $_SESSION['login_attempts']['count'] = 0;
}

// Пытаемся аутентифицировать
$result = authenticate($data['username'], $data['password']);

if ($result) {
    // Успешная авторизация
    $_SESSION['login_attempts']['count'] = 0;
    $_SESSION['auth_token'] = $result['token'];
    $_SESSION['auth_time'] = time();
    $_SESSION['username'] = $data['username'];
    $_SESSION['display_name'] = $data['username'] === 'root' ? 'Администратор' : $data['username'];
    
    // Устанавливаем cookie для дополнительной безопасности
    setcookie('PHPSESSID', session_id(), [
        'expires' => time() + 3600,
        'path' => '/',
        'domain' => 'localhost',
        'secure' => false, // Установите true если используете HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Отправляем ответ клиенту
    echo json_encode(['success' => true, 'token' => $result['token']]);
} else {
    // Неудачная попытка
    $_SESSION['login_attempts']['count']++;
    $_SESSION['login_attempts']['last_attempt'] = $now;
    
    http_response_code(401);
    echo json_encode(['error' => 'Неверный логин или пароль']);
} 