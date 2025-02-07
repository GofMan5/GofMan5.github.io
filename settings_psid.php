<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

// Подключаем необходимые файлы
require_once 'auth.php';
require_once 'database.php';

// Запускаем сессию
session_start();

// Проверяем наличие токена в сессии
if (!isset($_SESSION['auth_token']) || !checkSession($_SESSION['auth_token'])) {
    header('Location: index.html');
    exit();
}

// Проверяем роль пользователя
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT role FROM administrators WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$admin = $stmt->fetch();

// Проверяем доступ для root пользователя
if ($_SESSION['username'] === 'root') {
    $admin['role'] = 'root';
}

if ($admin['role'] !== 'HEAD PSID' && $admin['role'] !== 'root') {
    header('Location: admin.php');
    exit();
}

// Принудительно указываем тип контента
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройка баллов PSID | EMS</title>
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
            --psid-color: #8e44ad;
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
            padding: 8px;
        }

        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .settings-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-title h1 {
            font-size: 1.2rem;
            margin: 0;
            color: var(--text-color);
        }

        .settings-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .settings-button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .settings-button:hover {
            background: var(--hover-bg);
            transform: translateY(-1px);
        }

        .category-card {
            background: var(--card-bg);
            border-radius: 8px;
            margin-bottom: 6px;
            overflow: hidden;
        }

        .category-header {
            padding: 8px;
            color: white;
            font-weight: bold;
            background: var(--psid-color);
        }

        .action-row {
            display: flex;
            align-items: center;
            padding: 8px;
            gap: 8px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .action-name {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .points-input {
            width: 60px;
            padding: 4px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
        }

        .save-button {
            width: 100%;
            padding: 12px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            transition: all 0.2s ease;
        }

        .save-button:hover {
            background: #27ae60;
            transform: translateY(-1px);
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="settings-header">
            <div class="settings-title">
                <i class="fas fa-brain"></i>
                <h1>Настройка баллов PSID</h1>
            </div>
            <div class="settings-controls">
                <button type="button" class="settings-button" onclick="setDefaultValues()">
                    <i class="fas fa-sync"></i>
                    Стандартные настройки
                </button>
                <button class="settings-button" onclick="location.href='admin.php'">
                    <i class="fas fa-arrow-left"></i>
                    Назад
                </button>
            </div>
        </div>

        <form id="psidSettingsForm">
            <!-- Основные действия -->
            <div class="category-card">
                <div class="category-header">Основные действия</div>
                <div class="action-row">
                    <div class="action-name" data-action="pills">
                        Выдача таблетки (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="pills" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="vaccination">
                        Выдача вакцины (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="vaccination" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="certificate">
                        Выдача мед. карты (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="certificate" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Специальные действия -->
            <div class="category-card">
                <div class="category-header">Специальные действия</div>
                <div class="action-row">
                    <div class="action-name" data-action="govCheck">
                        Проверка госструктур (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="govCheck" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="psychoHelp">
                        Оказание псих. помощи (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="psychoHelp" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="dutyHalfHour">
                        Дежурство пол часа в больнице (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="dutyHalfHour" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="jailExamination">
                        Обследование пациента на выезде в КПЗ (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="jailExamination" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="therapistExam">
                        Принятие экзамена на терапевта (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="therapistExam" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Оказание помощи -->
            <div class="category-card">
                <div class="category-header">Оказание помощи</div>
                <div class="action-row">
                    <div class="action-name" data-action="firstAidBefore22">
                        Оказание ПМП ДО 22:00 (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="firstAidBefore22" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="firstAidAfter22">
                        Оказание ПМП ПОСЛЕ 22:00 (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="firstAidAfter22" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Мероприятия -->
            <div class="category-card">
                <div class="category-header">Мероприятия</div>
                <div class="action-row">
                    <div class="action-name" data-action="delivery">
                        Участие в поставке (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="delivery" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="event">
                        Участие в МП/ГМП (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="event" min="0" step="0.5" required>
                </div>
            </div>

            <button type="submit" class="save-button">
                <i class="fas fa-save"></i>
                Сохранить изменения
            </button>
        </form>
    </div>

    <script>
        // Функция форматирования названия действия
        function formatActionName(baseName, value) {
            return `${baseName} (${value || 'Не установлено'})`;
        }

        // Функция обновления названий действий
        function updateActionNames(settings) {
            Object.entries(settings).forEach(([key, value]) => {
                const element = document.querySelector(`[data-action="${key}"]`);
                if (element) {
                    const baseName = element.textContent.split('(')[0].trim();
                    element.textContent = formatActionName(baseName, value);
                }
            });
        }

        // Функция установки стандартных значений
        function setDefaultValues() {
            if (confirm('Вы уверены, что хотите установить стандартные настройки? Все текущие настройки будут заменены.')) {
                const defaultValues = {
                    pills: 1,
                    vaccination: 2,
                    certificate: 2,
                    govCheck: 15,
                    psychoHelp: 20,
                    dutyHalfHour: 3,
                    firstAidBefore22: 2,
                    firstAidAfter22: 3,
                    delivery: 20,
                    event: 30,
                    jailExamination: 15,
                    therapistExam: 5
                };

                const form = document.getElementById('psidSettingsForm');

                // Устанавливаем значения
                Object.entries(defaultValues).forEach(([key, value]) => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = value;
                    }
                });

                updateActionNames(defaultValues);
            }
        }

        // Функция загрузки настроек
        async function loadSettings() {
            try {
                const response = await fetch('settings_api.php?department=psid', {
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.settings) {
                        const form = document.getElementById('psidSettingsForm');
                        
                        // Устанавливаем значения
                        Object.entries(data.settings).forEach(([key, value]) => {
                            const input = form.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.value = value;
                            }
                        });
                        
                        updateActionNames(data.settings);
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки настроек:', error);
            }
        }

        // Загрузка настроек при загрузке страницы
        document.addEventListener('DOMContentLoaded', loadSettings);

        // Обработчик отправки формы
        document.getElementById('psidSettingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {};
            
            // Собираем значения
            this.querySelectorAll('.points-input').forEach(input => {
                formData[input.name] = parseFloat(input.value) || 0;
            });

            try {
                const response = await fetch('settings_api.php?department=psid', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData),
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        alert('Настройки успешно сохранены');
                        window.location.reload();
                    } else {
                        alert(result.error || 'Ошибка сохранения настроек');
                    }
                }
            } catch (error) {
                console.error('Ошибка сохранения настроек:', error);
                alert('Произошла ошибка при сохранении настроек');
            }
        });
    </script>
</body>
</html> 