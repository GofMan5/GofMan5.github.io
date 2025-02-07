<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

// Подключаем файл аутентификации
require_once 'auth.php';
require_once 'database.php';

// Запускаем сессию
session_start();

// Проверяем наличие токена в сессии
if (!isset($_SESSION['auth_token']) || !checkSession($_SESSION['auth_token'])) {
    header('Location: index.html');
    exit();
}

// Функция получения статистики
function getStatistics() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Посещения сегодня (уникальные по IP и User Agent)
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT COUNT(*) as count 
            FROM (
                SELECT DISTINCT visitor_ip, user_agent 
                FROM `site_visits` 
                WHERE DATE(visited_at) = ?
            ) as unique_visits");
        $stmt->execute([$today]);
        $visitsToday = (int)$stmt->fetchColumn();

        // Всего уникальных посещений
        $stmt = $db->query("SELECT COUNT(*) as count 
            FROM (
                SELECT DISTINCT visitor_ip, user_agent 
                FROM `site_visits`
            ) as total_unique_visits");
        $totalVisits = (int)$stmt->fetchColumn();

        // Количество загрузок на imgur
        $stmt = $db->query("SELECT COUNT(*) as count FROM `imgur_uploads`");
        $imgurUploads = (int)$stmt->fetchColumn();

        return [
            'visits_today' => $visitsToday,
            'total_visits' => $totalVisits,
            'imgur_uploads' => $imgurUploads
        ];
    } catch (PDOException $e) {
        error_log("Ошибка получения статистики: " . $e->getMessage());
        return [
            'visits_today' => 0,
            'total_visits' => 0,
            'imgur_uploads' => 0
        ];
    }
}

// Если это AJAX запрос на обновление статистики
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && 
    isset($_GET['action']) && $_GET['action'] === 'get_statistics') {
    header('Content-Type: application/json');
    echo json_encode(getStatistics());
    exit;
}

// Получаем роль пользователя
$userRole = 'root';
if ($_SESSION['username'] !== 'root') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT role FROM administrators WHERE username = ?");
        $stmt->execute([$_SESSION['username']]);
        $admin = $stmt->fetch();
        if ($admin) {
            $userRole = $admin['role'];
        }
    } catch (PDOException $e) {
        error_log("Ошибка получения роли пользователя: " . $e->getMessage());
    }
}

// Получаем статистику
$stats = getStatistics();

// Устанавливаем обработку PHP
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

// Принудительно указываем тип контента
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель EMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #2ecc71;
            --danger-color: #e74c3c;
            --text-color: #e4e6eb;
            --bg-color: #18191a;
            --card-bg: #242526;
            --input-bg: #3a3b3c;
            --border-color: #393a3b;
            --hover-bg: #4e4f50;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s ease;
            --header-bg: #2d2e30;
            --text-color-secondary: #b1b3b5;
            --primary-color-hover: #357abd;
            --surface-color: #242526;
            --primary-dark-color: #357abd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            padding: 20px;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        
        .admin-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
        }
        
        .admin-controls {
            display: flex;
            gap: 10px;
        }
        
        .admin-button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.95rem;
            width: auto;
            justify-content: flex-start;
        }
        
        .admin-button:hover {
            background: var(--primary-dark-color);
        }
        
        .admin-button.danger {
            background: var(--danger-color);
        }
        
        .admin-button.danger:hover {
            background: #c0392b;
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .admin-card {
            background: var(--surface-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: #e4e6eb;
            font-size: 16px;
            font-weight: 600;
        }
        
        .card-header h3 i {
            color: #4a90e2;
            font-size: 18px;
        }
        
        .refresh-button {
            display: none;
        }
        
        .refresh-button i {
            display: none;
        }
        
        .refresh-button:hover {
            display: none;
        }
        
        .refresh-button.rotating i {
            display: none;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 10px;
        }
        
        .stat-item {
            background: rgba(30, 31, 32, 0.6);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 90px;
        }
        
        .stat-item:hover {
            background: rgba(30, 31, 32, 0.8);
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #4a90e2;
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .stat-label {
            color: #a0a0a0;
            font-size: 11px;
            line-height: 1.2;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            max-width: 100px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .stat-grid {
                gap: 8px;
            }
            
            .stat-value {
                font-size: 20px;
            }
            
            .stat-label {
                font-size: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .stat-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stat-item {
                padding: 15px;
            }
            
            .stat-value {
                font-size: 1.8rem;
            }
        }

        /* Стили для списка администраторов */
        .admin-list {
            background: var(--surface-color);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .admin-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }

        .admin-item:last-child {
            border-bottom: none;
        }

        .admin-item:hover {
            background-color: var(--hover-bg);
        }

        .admin-actions {
            display: flex;
            gap: 8px;
        }

        .admin-actions button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 0.9rem;
        }

        .edit-admin {
            background: var(--primary-color);
            color: white;
        }

        .edit-admin:hover {
            background: var(--primary-dark-color);
        }

        .delete-admin {
            background: var(--danger-color);
            color: white;
        }

        .delete-admin:hover {
            background: #c0392b;
        }

        #add-admin-form {
            background: var(--surface-color);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        #add-admin-form input,
        #add-admin-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 12px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
        }

        #add-admin-form button {
            width: 100%;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        #add-admin-form button:hover {
            background: var(--primary-dark-color);
        }

        /* Стили для модального окна */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: var(--card-bg);
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--input-bg);
            color: var(--text-color);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Стили для таблицы администраторов */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface-color);
            margin-top: 10px;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .admin-table th {
            font-weight: 600;
            font-size: 14px;
            color: #a0a0a0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            font-size: 14px;
        }

        .admin-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .admin-table .actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .admin-table .admin-button {
            padding: 8px 12px;
            font-size: 13px;
            min-width: auto;
        }

        /* Стили для информационного модального окна */
        .info-modal {
            display: none;
            position: fixed;
            z-index: 1100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .info-modal-content {
            background: var(--surface-color);
            margin: 15% auto;
            padding: 0;
            width: 90%;
            max-width: 400px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideIn 0.3s ease;
        }

        .info-modal-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .info-modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .info-modal-body {
            padding: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            color: var(--text-color-secondary);
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: var(--text-color);
            font-size: 16px;
            font-weight: 500;
        }

        .info-modal-footer {
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .info-modal-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
            display: block;
        }

        .info-modal-btn:hover {
            background: var(--primary-dark-color);
            transform: translateY(-1px);
        }

        .info-modal-btn:active {
            transform: translateY(0);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(20px); opacity: 0; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Добавляем стили для инпутов и кнопок */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--input-bg);
            color: var(--text-color);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 14px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            color: var(--text-color-secondary);
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .admin-button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            background: var(--primary-color);
            color: white;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-button:hover {
            background: var(--primary-dark-color);
            transform: translateY(-1px);
        }

        .admin-button:active {
            transform: translateY(0);
        }

        .admin-button.danger {
            background: var(--danger-color);
        }

        .admin-button.danger:hover {
            background: #c0392b;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23ffffff' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 12px) center;
            padding-right: 35px;
        }

        /* Обновляем стили для таблицы и кнопок действий */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .admin-table th {
            background: var(--header-bg);
            padding: 15px 20px;
            font-weight: 500;
            text-align: left;
            color: var(--text-color);
            font-size: 14px;
        }

        .admin-table td {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .admin-table tr:hover {
            background: var(--hover-bg);
        }

        .admin-table .actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .admin-table .admin-button {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .admin-table .admin-button i {
            font-size: 14px;
        }

        /* Стили для модального окна редактирования */
        .edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.2s ease;
        }

        .edit-modal-content {
            background: var(--surface-color);
            width: 90%;
            max-width: 400px;
            margin: 10% auto;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease;
        }

        .edit-modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .edit-modal-header h2 {
            margin: 0;
            font-size: 18px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .edit-modal-body {
            padding: 20px;
        }

        .edit-form-group {
            margin-bottom: 20px;
        }

        .edit-form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-size: 14px;
            font-weight: 500;
        }

        .edit-form-group input,
        .edit-form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .edit-form-group input:focus,
        .edit-form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        .edit-modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
        }

        .edit-modal-footer .admin-button {
            min-width: 200px;
            justify-content: center;
        }

        /* Обновляем стили для иконки глаза и контейнера пароля */
        .password-input-wrapper {
            position: relative;
            width: 100%;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-color-secondary);
            transition: color 0.3s ease;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .info-modal .password-container {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-modal .password-toggle {
            position: static;
            transform: none;
        }

        .close-btn {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            color: var(--text-color);
            opacity: 0.7;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            width: 32px;
            height: 32px;
        }

        .close-btn:hover {
            opacity: 1;
            background-color: rgba(0, 0, 0, 0.1);
        }

        .close-btn i {
            font-size: 16px;
        }

        /* Удаляем неиспользуемые стили */
        .refresh-button,
        .refresh-button i,
        .refresh-button:hover,
        .refresh-button.rotating i,
        .admin-list,
        .admin-item,
        .admin-actions {
            display: none;
        }

        /* Объединяем общие стили модальных окон */
        .modal-base {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content-base {
            background: var(--surface-color);
            margin: 10% auto;
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        /* Наследуем базовые стили */
        .modal,
        .edit-modal,
        .info-modal {
            @extend .modal-base;
        }

        .modal-content,
        .edit-modal-content,
        .info-modal-content {
            @extend .modal-content-base;
        }

        /* Стили для модального окна подтверждения удаления */
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            animation: fadeIn 0.2s ease;
        }

        .confirm-modal-content {
            background: var(--surface-color);
            width: 90%;
            max-width: 400px;
            margin: 15% auto;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transform: translateY(0);
            animation: slideIn 0.3s ease;
        }

        .confirm-modal-header {
            background: var(--danger-color);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }

        .confirm-modal-header i {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }

        .confirm-modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 500;
        }

        .confirm-modal-body {
            padding: 25px 20px;
            text-align: center;
            color: var(--text-color);
            font-size: 16px;
            line-height: 1.5;
        }

        .confirm-modal-footer {
            padding: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .confirm-modal-footer button {
            min-width: 120px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .confirm-modal-footer .confirm-delete {
            background: var(--danger-color);
            color: white;
        }

        .confirm-modal-footer .confirm-delete:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }

        .confirm-modal-footer .cancel-delete {
            background: var(--input-bg);
            color: var(--text-color);
        }

        .confirm-modal-footer .cancel-delete:hover {
            background: var(--hover-bg);
            transform: translateY(-1px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Функция загрузки администраторов
        function loadAdministrators() {
            $.get('admin_api.php')
                .done(function(response) {
                    if (response.success) {
                        const adminList = $('#adminList');
                        adminList.empty();
                        
                        if (response.data && response.data.length > 0) {
                            response.data.forEach(function(admin) {
                                const row = $('<tr>');
                                
                                // Добавляем ячейку с именем пользователя
                                row.append($('<td>').text(admin.username));
                                
                                // Добавляем ячейку с ролью
                                row.append($('<td>').text(admin.role));
                                
                                // Добавляем ячейку с действиями
                                const actions = $('<td>').addClass('actions');
                                
                                // Кнопка редактирования
                                const editBtn = $('<button>')
                                    .addClass('admin-button')
                                    .html('<i class="fas fa-edit"></i> Редактировать')
                                    .on('click', function() {
                                        editAdmin(admin);
                                    });
                                
                                // Кнопка удаления
                                const deleteBtn = $('<button>')
                                    .addClass('admin-button danger')
                                    .html('<i class="fas fa-trash"></i> Удалить')
                                    .on('click', function() {
                                        deleteAdmin(admin.id);
                                    });
                                
                                actions.append(editBtn).append(deleteBtn);
                                row.append(actions);
                                
                                adminList.append(row);
                            });
                        } else {
                            adminList.append('<tr><td colspan="3" style="text-align: center; padding: 20px;">Нет администраторов</td></tr>');
                        }
                    } else {
                        console.error('Ошибка загрузки данных:', response);
                        alert('Ошибка загрузки данных');
                    }
                })
                .fail(function(xhr) {
                    console.error('Ошибка запроса:', xhr);
                    alert('Ошибка загрузки данных');
                });
        }

        // Определяем функцию editAdmin глобально
        function editAdmin(admin) {
            $('#edit-admin-id').val(admin.id);
            $('#edit-username').val(admin.username);
            $('#edit-role').val(admin.role);
            $('#edit-password').val('');
            $('#editAdminModal').fadeIn(200);
        }

        // Определяем функцию deleteAdmin глобально
        function deleteAdmin(id) {
            const modal = $('#confirmDeleteModal');
            const confirmBtn = $('#confirmDelete');
            
            // Сохраняем ID для использования при подтверждении
            confirmBtn.data('admin-id', id);
            
            // Показываем модальное окно
            modal.fadeIn(200);
            
            // Анимация иконки
            const icon = modal.find('.fa-exclamation-triangle');
            icon.css('animation', 'shake 0.5s ease');
            setTimeout(() => icon.css('animation', ''), 500);
        }

        // Обработчик подтверждения удаления
        $(document).ready(function() {
            $('#confirmDelete').click(function() {
                const id = $(this).data('admin-id');
                
                $.ajax({
                    url: 'admin_api.php?id=' + id,
                    method: 'DELETE'
                })
                .done(function(response) {
                    if (response.success) {
                        $('#confirmDeleteModal').fadeOut(200);
                        loadAdministrators();
                    }
                })
                .fail(function() {
                    alert('Ошибка при удалении администратора');
                });
            });

            // Обработчик отмены удаления
            $('#cancelDelete').click(function() {
                $('#confirmDeleteModal').fadeOut(200);
            });
        });

        // Определяем функцию saveAdminChanges глобально
        function saveAdminChanges() {
            const adminId = $('#edit-admin-id').val();
            const formData = {
                username: $('#edit-username').val(),
                role: $('#edit-role').val()
            };

            const newPassword = $('#edit-password').val();
            if (newPassword) {
                formData.new_password = newPassword;
            }

            $.ajax({
                url: 'admin_api.php?id=' + adminId,
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        $('#editAdminModal').hide();
                        loadAdministrators();
                        alert('Администратор успешно обновлен');
                    } else {
                        alert(response.error || 'Ошибка при обновлении администратора');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Ошибка при обновлении администратора';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage += '\n' + xhr.responseJSON.error;
                    }
                    alert(errorMessage);
                }
            });
        }

        // Инициализация при загрузке документа
        $(document).ready(function() {
            // Проверка прав доступа
            const isAdmin = <?php echo json_encode($_SESSION['username'] === 'root' || $userRole === 'moderator'); ?>;
            
            // Загружаем список администраторов только если есть права
            if (isAdmin) {
                loadAdministrators();
            }

            // Функция показа информационного окна
            function showInfoModal(username, role, password) {
                $('#info-username').text(username || '');
                $('#info-role').text(role || '');
                
                // Создаем контейнер для пароля с кнопкой показать/скрыть
                const passwordContainer = $('<div>').addClass('password-container');
                const passwordValue = $('<span>').text('•'.repeat(password.length));
                const toggleBtn = $('<i>')
                    .addClass('fas fa-eye password-toggle')
                    .click(function() {
                        if (passwordValue.text() === password) {
                            passwordValue.text('•'.repeat(password.length));
                            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                        } else {
                            passwordValue.text(password);
                            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                        }
                    });
                
                passwordContainer.append(passwordValue).append(toggleBtn);
                $('#info-password').html(passwordContainer);
                
                // Показываем модальное окно
                $('#infoModal').fadeIn(200);
            }

            // Обработчик кнопки "Понятно" в информационном окне
            $('#closeInfoBtn').click(function() {
                $('#infoModal').fadeOut(200);
            });

            // Общий обработчик закрытия модальных окон
            function closeModal(modalSelector) {
                $(modalSelector).fadeOut(200);
            }

            // Обработчики для всех модальных окон
            $('.close, .close-btn').click(function() {
                closeModal($(this).closest('.modal, .edit-modal, .info-modal'));
            });

            $(window).click(function(event) {
                if ($(event.target).hasClass('modal') || 
                    $(event.target).hasClass('edit-modal') || 
                    $(event.target).hasClass('info-modal')) {
                    closeModal(event.target);
                }
            });

            // Предотвращаем закрытие при клике на контент
            $('.modal-content, .edit-modal-content, .info-modal-content').click(function(event) {
                event.stopPropagation();
            });

            // Обработчик показа формы добавления администратора
            $('#show-admin-management').click(function() {
                if (!isAdmin) {
                    alert('У вас нет прав доступа к этой функции');
                    return;
                }
                $('#adminModal').fadeIn(200);
            });

            // Обработчик для иконки глаза
            $('.password-toggle').click(function() {
                const input = $(this).siblings('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });

            // Обработчик формы добавления администратора
            $('#add-admin-form').submit(function(e) {
                e.preventDefault();
                
                const formData = {
                    username: $('#new-admin-username').val(),
                    role: $('#new-admin-role').val(),
                    new_password: $('#new-admin-password').val()
                };

                $.ajax({
                    url: 'admin_api.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(response) {
                        if (response.success) {
                            $('#adminModal').hide();
                            $('#add-admin-form')[0].reset();
                            showInfoModal(response.data.username, response.data.role, response.data.password);
                            loadAdministrators();
                        } else {
                            alert(response.error || 'Ошибка при добавлении администратора');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Ошибка при добавлении администратора';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage += '\n' + xhr.responseJSON.error;
                        }
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-title">
                <i class="fas fa-shield-alt"></i>
                <h1>Панель администратора | <?php 
                    if ($_SESSION['username'] === 'root') {
                        echo 'root';
                    } else {
                        echo htmlspecialchars($_SESSION['username']) . ' | ' . htmlspecialchars($userRole);
                    }
                ?></h1>
            </div>
            <div class="admin-controls">
                <button class="admin-button" onclick="location.href='index.html'">
                    <i class="fas fa-home"></i>
                    На главную
                </button>
                <button class="admin-button danger" onclick="location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    Выход
                </button>
            </div>
        </div>

        <div class="admin-grid">
            <!-- Блок статистики -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Статистика</h3>
                </div>
                <div class="stat-grid">
                    <div class="stat-item">
                        <div class="stat-value" id="visits-today"><?php echo number_format($stats['visits_today']); ?></div>
                        <div class="stat-label">Посещений сегодня</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value" id="total-visits"><?php echo number_format($stats['total_visits']); ?></div>
                        <div class="stat-label">Всего посещений</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value" id="imgur-uploads"><?php echo number_format($stats['imgur_uploads']); ?></div>
                        <div class="stat-label">Загрузок на imgur</div>
                    </div>
                </div>
            </div>

            <!-- Блок управления системой -->
            <?php if ($_SESSION['username'] === 'root' || $userRole === 'moderator'): ?>
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-tools"></i> Управление системой</h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button class="admin-button" style="width: 100%;" id="show-admin-management">
                        <i class="fas fa-user-plus"></i>
                        Добавить администратора
                    </button>
                    <button class="admin-button" style="width: 100%;">
                        <i class="fas fa-cog"></i>
                        Настройки системы
                    </button>
                    <button class="admin-button" style="width: 100%;">
                        <i class="fas fa-database"></i>
                        Резервное копирование
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Блок настройки баллов отделов -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Настройка баллов отделов</h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php if ($userRole === 'root' || $userRole === 'moderator' || $userRole === 'HEAD PM'): ?>
                    <button class="admin-button" style="width: 100%;" onclick="location.href='settings_pm.php'">
                        <i class="fas fa-ambulance"></i>
                        Настройка баллов PM
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($userRole === 'root' || $userRole === 'moderator' || $userRole === 'HEAD DI'): ?>
                    <button class="admin-button" style="width: 100%;" onclick="location.href='settings_di.php'">
                        <i class="fas fa-search"></i>
                        Настройка баллов DI
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($userRole === 'root' || $userRole === 'moderator' || $userRole === 'HEAD PSID'): ?>
                    <button class="admin-button" style="width: 100%;" onclick="location.href='settings_psid.php'">
                        <i class="fas fa-brain"></i>
                        Настройка баллов PSID
                    </button>
                    <?php endif; ?>

                    <?php if ($userRole === 'root' || $userRole === 'moderator'): ?>
                    <button class="admin-button" style="width: 100%;" onclick="location.href='settings_had.php'">
                        <i class="fas fa-hospital"></i>
                        Настройка баллов HAD
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Блок списка администраторов -->
            <?php if ($_SESSION['username'] === 'root' || $userRole === 'moderator'): ?>
            <div class="admin-card" style="grid-column: 1 / -1;">
                <div class="card-header">
                    <h3><i class="fas fa-users-cog"></i> Действующие администраторы</h3>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Имя пользователя</th>
                                <th>Роль</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="adminList">
                            <!-- Список администраторов будет загружен через JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Модальное окно управления администраторами -->
        <div id="adminModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-user-plus"></i> Добавление администратора</h2>
                    <span class="close">&times;</span>
                </div>
                
                <!-- Форма добавления администратора -->
                <form id="add-admin-form">
                    <div class="form-group">
                        <label for="new-admin-username">Имя пользователя</label>
                        <input type="text" id="new-admin-username" required>
                    </div>
                    <div class="form-group">
                        <label for="new-admin-role">Роль</label>
                        <select id="new-admin-role" required>
                            <option value="moderator">Модератор</option>
                            <option value="HEAD PM">HEAD PM</option>
                            <option value="HEAD PSID">HEAD PSID</option>
                            <option value="HEAD DI">HEAD DI</option>
                            <option value="HEAD HAD">HEAD HAD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new-admin-password">Пароль</label>
                        <input type="password" id="new-admin-password">
                        <i class="fas fa-eye password-toggle"></i>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="admin-button">Добавить</button>
                        <button type="button" class="admin-button danger" onclick="$('#adminModal').hide()">Отмена</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Модальное окно редактирования администратора -->
        <div id="editAdminModal" class="edit-modal">
            <div class="edit-modal-content">
                <div class="edit-modal-header">
                    <h2><i class="fas fa-user-edit"></i> Редактирование администратора</h2>
                    <button class="close-btn"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="edit-modal-body">
                    <form id="edit-admin-form">
                        <input type="hidden" id="edit-admin-id">
                        <div class="edit-form-group">
                            <label for="edit-username">Имя пользователя</label>
                            <input type="text" id="edit-username" required>
                        </div>
                        <div class="edit-form-group">
                            <label for="edit-role">Роль</label>
                            <select id="edit-role" required>
                                <option value="moderator">Модератор</option>
                                <option value="HEAD PM">HEAD PM</option>
                                <option value="HEAD PSID">HEAD PSID</option>
                                <option value="HEAD DI">HEAD DI</option>
                            </select>
                        </div>
                        <div class="edit-form-group">
                            <label for="edit-password">Новый пароль</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="edit-password" placeholder="Оставьте пустым, чтобы не менять">
                                <i class="fas fa-eye password-toggle"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="edit-modal-footer">
                    <button type="button" class="admin-button" onclick="saveAdminChanges()">Сохранить</button>
                </div>
            </div>
        </div>

        <!-- Информационное модальное окно -->
        <div id="infoModal" class="info-modal">
            <div class="info-modal-content">
                <div class="info-modal-header">
                    <h3><i class="fas fa-check-circle"></i> Администратор успешно добавлен</h3>
                </div>
                <div class="info-modal-body">
                    <div class="info-item">
                        <div class="info-label">Логин</div>
                        <div class="info-value" id="info-username"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Роль</div>
                        <div class="info-value" id="info-role"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Пароль</div>
                        <div class="info-value" id="info-password"></div>
                    </div>
                </div>
                <div class="info-modal-footer">
                    <button type="button" class="info-modal-btn" id="closeInfoBtn">Понятно</button>
                </div>
            </div>
        </div>

        <!-- Модальное окно подтверждения удаления -->
        <div id="confirmDeleteModal" class="confirm-modal">
            <div class="confirm-modal-content">
                <div class="confirm-modal-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Подтвердите действие на localhost:8080</h3>
                </div>
                <div class="confirm-modal-body">
                    Вы уверены, что хотите удалить этого администратора?
                </div>
                <div class="confirm-modal-footer">
                    <button id="confirmDelete" class="confirm-delete">
                        <i class="fas fa-check"></i> OK
                    </button>
                    <button id="cancelDelete" class="cancel-delete">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>