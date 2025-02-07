<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

require_once 'database.php';

// Включаем вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

// Проверяем, что запрос пришел через POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Неверный метод запроса: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    exit('Method Not Allowed');
}

// Получаем данные о посетителе
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$page = isset($_POST['page']) ? $_POST['page'] : 'index';

// Нормализуем названия страниц калькуляторов
$calculatorPages = [
    'calculator_pm' => 'calculator_pm.html',
    'calculator_di' => 'calculator_di.html',
    'calculator_psid' => 'calculator_psid.html',
    'calculator_had' => 'calculator_had.html'
];

if (isset($calculatorPages[$page])) {
    $page = $calculatorPages[$page];
}

// Подробное логирование
error_log("=== Новая попытка записи посещения ===");
error_log("Время: " . date('Y-m-d H:i:s'));
error_log("IP: " . $ip);
error_log("User Agent: " . $userAgent);
error_log("Page: " . $page);
error_log("POST данные: " . print_r($_POST, true));

try {
    $db = Database::getInstance()->getConnection();
    error_log("Соединение с БД установлено");
    
    // Проверяем существование таблицы
    $tableExists = $db->query("SHOW TABLES LIKE 'site_visits'")->rowCount() > 0;
    error_log("Таблица site_visits существует: " . ($tableExists ? 'Да' : 'Нет'));
    
    if (!$tableExists) {
        error_log("Создаем таблицу site_visits");
        // Создаем таблицу если её нет
        $db->exec("CREATE TABLE IF NOT EXISTS `site_visits` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `visitor_ip` VARCHAR(45) NOT NULL,
            `user_agent` VARCHAR(255) NOT NULL,
            `visited_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `page` VARCHAR(50) NOT NULL,
            INDEX `idx_visitor_ip` (`visitor_ip`),
            INDEX `idx_visited_at` (`visited_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        error_log("Таблица site_visits создана");
    }
    
    // Проверяем, было ли посещение с этого IP в последние 5 минут
    $stmt = $db->prepare("SELECT COUNT(*) FROM `site_visits` 
        WHERE visitor_ip = ? 
        AND user_agent = ?
        AND page = ?
        AND visited_at >= NOW() - INTERVAL 5 MINUTE");
    $stmt->execute([$ip, $userAgent, $page]);
    $recentVisits = $stmt->fetchColumn();

    error_log("Недавних посещений найдено: " . $recentVisits);

    if ($recentVisits == 0) {
        // Записываем новое посещение
        $stmt = $db->prepare("INSERT INTO `site_visits` (visitor_ip, user_agent, page) VALUES (?, ?, ?)");
        $success = $stmt->execute([$ip, $userAgent, $page]);
        
        if ($success) {
            error_log("Успешно записано новое посещение");
            echo json_encode(['success' => true]);
        } else {
            error_log("Ошибка при записи посещения: " . print_r($stmt->errorInfo(), true));
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to log visit']);
        }
    } else {
        error_log("Посещение уже было зарегистрировано");
        echo json_encode(['success' => true, 'message' => 'Recent visit exists']);
    }
} catch (PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

error_log("=== Конец записи посещения ===\n");