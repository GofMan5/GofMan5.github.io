<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

require_once 'database.php';
require_once 'auth.php';

// Запускаем сессию
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['auth_token']) || !checkSession($_SESSION['auth_token'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

// Получаем роль пользователя
$db = Database::getInstance()->getConnection();
$userRole = 'root';
if ($_SESSION['username'] !== 'root') {
    $stmt = $db->prepare("SELECT role FROM administrators WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $admin = $stmt->fetch();
    if ($admin) {
        $userRole = $admin['role'];
    }
}

// Проверяем доступ к настройкам отдела
$department = $_GET['department'] ?? '';
$hasAccess = false;

switch ($department) {
    case 'pm':
        $hasAccess = ($userRole === 'HEAD PM' || $userRole === 'root');
        break;
    case 'di':
        $hasAccess = ($userRole === 'HEAD DI' || $userRole === 'root');
        break;
    case 'psid':
        $hasAccess = ($userRole === 'HEAD PSID' || $userRole === 'root');
        break;
    case 'had':
        $hasAccess = ($userRole === 'HAD' || $userRole === 'root');
        break;
    default:
        http_response_code(400);
        die(json_encode(['error' => 'Неверный отдел']));
}

if (!$hasAccess) {
    http_response_code(403);
    die(json_encode(['error' => 'Доступ запрещен']));
}

// Создаем таблицу если её нет
try {
    $db->exec("CREATE TABLE IF NOT EXISTS department_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department VARCHAR(10) NOT NULL,
        settings JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_department (department)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    error_log("Ошибка создания таблицы: " . $e->getMessage());
}

header('Content-Type: application/json');

// Обработка запросов
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            $stmt = $db->prepare("SELECT settings FROM department_settings WHERE department = ?");
            $stmt->execute([$department]);
            $result = $stmt->fetch();

            // Определяем дефолтные значения в зависимости от отдела
            switch ($department) {
                case 'pm':
                    $defaultSettings = [
                        // ELS Hospital
                        'pillsEls' => 1,
                        'pillsElsNight' => 1.5,
                        'vaccinationEls' => 2,
                        'vaccinationElsNight' => 3,
                        'certificateEls' => 3,
                        'certificateElsNight' => 4.5,
                        
                        // Sandy Shores
                        'pillsSandy' => 2,
                        'pillsSandyNight' => 3,
                        'vaccinationSandy' => 3,
                        'vaccinationSandyNight' => 4.5,
                        'certificateSandy' => 4,
                        'certificateSandyNight' => 6,
                        
                        // Paleto Bay
                        'pillsPaleto' => 3,
                        'pillsPaletoNight' => 4.5,
                        'vaccinationPaleto' => 4,
                        'vaccinationPaletoNight' => 6,
                        'certificatePaleto' => 5,
                        'certificatePaletoNight' => 7.5,
                        
                        // Реанимация
                        'firstAid' => 3,
                        'firstAidNight' => 4.5,
                        'rejectedCall' => 1,
                        
                        // Для инструкторов
                        'reportCheck' => 10,
                        'examConduct' => 5,
                        'hospitalCheck' => 5,
                        'briefingAssistance' => 15
                    ];
                    break;

                case 'psid':
                    $defaultSettings = [
                        // Основные действия
                        'pills' => 1,
                        'vaccination' => 2,
                        'certificate' => 2,
                        'govCheck' => 15,
                        'psychoHelp' => 20,
                        'dutyHalfHour' => 3,
                        'firstAidBefore22' => 2,
                        'firstAidAfter22' => 3,
                        'delivery' => 20,
                        'event' => 30,
                        'jailExamination' => 15,
                        'therapistExam' => 5
                    ];
                    break;

                case 'di':
                    $defaultSettings = [
                        // Работа DI
                        'openInterview' => 8,
                        'closedInterview' => 10,
                        'onlineApplication' => 10,
                        'conductOpenInterview' => 15,
                        'participateOpenInterview' => 8,
                        'statuteExam' => 8,
                        'instruction' => 5,
                        'firstAidLecture' => 3,
                        'internReport' => 5,
                        'internHelp' => 1,
                        'internEvent' => 5,
                        'internBriefing' => 3,
                        
                        // Общие действия
                        'pills' => 1,
                        'firstAid' => 3,
                        'vaccination' => 2,
                        'certificate' => 3
                    ];
                    break;

                default:
                    $defaultSettings = [];
            }

            if ($result) {
                $settings = json_decode($result['settings'], true);
                // Объединяем сохраненные настройки с дефолтными
                $settings = array_merge($defaultSettings, $settings);
                echo json_encode([
                    'success' => true,
                    'settings' => $settings
                ]);
            } else {
                // Если настроек нет, сохраняем и возвращаем дефолтные
                $stmt = $db->prepare("INSERT INTO department_settings (department, settings) VALUES (?, ?)");
                $stmt->execute([$department, json_encode($defaultSettings)]);

                echo json_encode([
                    'success' => true,
                    'settings' => $defaultSettings
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка получения настроек: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                die(json_encode(['error' => 'Неверные данные']));
            }

            // Проверяем существование записи
            $stmt = $db->prepare("SELECT id FROM department_settings WHERE department = ?");
            $stmt->execute([$department]);
            $exists = $stmt->fetch();

            if ($exists) {
                // Обновляем существующие настройки
                $stmt = $db->prepare("UPDATE department_settings SET settings = ? WHERE department = ?");
                $stmt->execute([json_encode($data), $department]);
            } else {
                // Создаем новые настройки
                $stmt = $db->prepare("INSERT INTO department_settings (department, settings) VALUES (?, ?)");
                $stmt->execute([$department, json_encode($data)]);
            }

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка сохранения настроек: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается']);
} 