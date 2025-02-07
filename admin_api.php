<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

// Подключаем необходимые файлы
require_once 'auth.php';
require_once 'database.php';

// Запускаем сессию
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['auth_token']) || !checkSession($_SESSION['auth_token'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Проверяем, что пользователь root или модератор
if ($_SESSION['username'] !== 'root' && $_SESSION['role'] !== 'moderator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// Устанавливаем заголовок JSON
header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();

    // Создаем таблицу, если она не существует
    $db->exec("CREATE TABLE IF NOT EXISTS administrators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // GET запрос - получение списка администраторов
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->query("SELECT id, username, role FROM administrators WHERE username != 'root' ORDER BY id DESC");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Проверяем и устанавливаем значение роли по умолчанию, если оно пустое
        foreach ($admins as &$admin) {
            if (empty($admin['role'])) {
                // Обновляем пустую роль на moderator в базе данных
                $updateStmt = $db->prepare("UPDATE administrators SET role = 'moderator' WHERE id = ? AND role = ''");
                $updateStmt->execute([$admin['id']]);
                $admin['role'] = 'moderator';
            }
        }
        
        echo json_encode(['success' => true, 'data' => $admins]);
    }
    
    // POST запрос - добавление нового администратора
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Получаем данные из тела запроса
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['username']) || !isset($data['role'])) {
            throw new Exception('Missing required fields');
        }

        // Проверяем существование пользователя
        $stmt = $db->prepare("SELECT COUNT(*) FROM administrators WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Username already exists');
        }

        // Проверяем допустимость роли
        $allowedRoles = ['HEAD PM', 'HEAD PSID', 'HEAD DI', 'HEAD HAD', 'moderator'];
        if (!in_array($data['role'], $allowedRoles)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Недопустимая роль']);
            exit;
        }

        // Генерируем пароль, если не указан
        $password = isset($data['new_password']) && !empty($data['new_password']) 
            ? $data['new_password'] 
            : bin2hex(random_bytes(8));

        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Добавляем администратора
        $stmt = $db->prepare("INSERT INTO administrators (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute([$data['username'], $hashedPassword, $data['role']]);

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $db->lastInsertId(),
                'username' => $data['username'],
                'role' => $data['role'],
                'password' => $password
            ]
        ]);
    }
    
    // PUT запрос - обновление администратора
    else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!isset($_GET['id'])) {
            throw new Exception('Missing admin ID');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        // Проверяем существование администратора
        $stmt = $db->prepare("SELECT id FROM administrators WHERE id = ? AND username != 'root'");
        $stmt->execute([$_GET['id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Admin not found or cannot be modified');
        }

        // Проверяем допустимость роли
        $allowedRoles = ['HEAD PM', 'HEAD PSID', 'HEAD DI', 'moderator'];
        if (!in_array($data['role'], $allowedRoles)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Недопустимая роль']);
            exit;
        }

        // Формируем SQL запрос
        $sql = "UPDATE administrators SET username = ?, role = ?";
        $params = [$data['username'], $data['role']];

        // Если указан новый пароль, добавляем его в запрос
        if (isset($data['new_password']) && !empty($data['new_password'])) {
            $sql .= ", password_hash = ?";
            $params[] = password_hash($data['new_password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ? AND username != 'root'";
        $params[] = $_GET['id'];

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true]);
    }
    
    // DELETE запрос - удаление администратора
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id'])) {
            throw new Exception('Missing admin ID');
        }

        $stmt = $db->prepare("DELETE FROM administrators WHERE id = ? AND username != 'root'");
        $stmt->execute([$_GET['id']]);

        echo json_encode(['success' => true]);
    }
    
    else {
        throw new Exception('Invalid request method');
    }
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 