<?php
header('Content-Type: application/json');

// Защита от прямого доступа к файлу
if (!defined('SECURE_ACCESS')) {
    http_response_code(403);
    die(json_encode(['error' => 'Доступ запрещен']));
}

// Хеш пароля храним в отдельном конфиг-файле
require_once 'config.php';

function authenticate($username, $password) {
    error_log("=== Authentication attempt ===");
    error_log("Time: " . date('Y-m-d H:i:s'));
    error_log("Username: " . $username);
    
    if (empty($username) || empty($password)) {
        error_log("Error: Empty username or password");
        return false;
    }

    // Проверка для root пользователя
    if ($username === 'root') {
        $passwordHash = hash('sha256', $password);
        if ($passwordHash === ADMIN_PASSWORD_HASH) {
            $token = bin2hex(random_bytes(32));
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['auth_token'] = $token;
            $_SESSION['auth_time'] = time();
            $_SESSION['username'] = $username;
            return ['success' => true, 'token' => $token];
        }
        return false;
    }

    // Проверка для обычных администраторов
    try {
        require_once 'database.php';
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, password_hash, role FROM administrators WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        error_log("Found admin: " . ($admin ? "Yes" : "No"));
        if ($admin) {
            error_log("Stored hash: " . $admin['password_hash']);
            error_log("Password verification result: " . (password_verify($password, $admin['password_hash']) ? "Success" : "Failed"));
        }

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $token = bin2hex(random_bytes(32));
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['auth_token'] = $token;
            $_SESSION['auth_time'] = time();
            $_SESSION['username'] = $admin['username'];
            $_SESSION['display_name'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];
            error_log("Authentication successful!");
            return ['success' => true, 'token' => $token];
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }

    error_log("Authentication failed!");
    error_log("=== End of authentication attempt ===\n");
    return false;
}

// Проверка сессии
function checkSession($token) {
    if (empty($token)) {
        return false;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['auth_token']) || !isset($_SESSION['auth_time'])) {
        return false;
    }

    // Проверяем валидность токена и время жизни сессии (1 час)
    if ($_SESSION['auth_token'] === $token && (time() - $_SESSION['auth_time']) < 3600) {
        return true;
    }

    return false;
} 