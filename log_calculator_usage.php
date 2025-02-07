<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

require_once 'database.php';

// Проверяем, что запрос пришел через POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Получаем данные
$data = json_decode(file_get_contents('php://input'), true);
$calculatorType = $data['calculator_type'] ?? '';

// Проверяем тип калькулятора
if (!in_array($calculatorType, ['pm', 'di', 'psid', 'had'])) {
    http_response_code(400);
    exit('Invalid calculator type');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Записываем использование калькулятора
    $stmt = $db->prepare("INSERT INTO calculator_usage (calculator_type) VALUES (?)");
    $success = $stmt->execute([$calculatorType]);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to log calculator usage']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    error_log("Ошибка записи использования калькулятора: " . $e->getMessage());
} 