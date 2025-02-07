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

if ($admin['role'] !== 'HAD' && $admin['role'] !== 'root') {
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
    <title>Настройка баллов HAD | EMS</title>
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
            --had-color: #8e44ad;
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

        .hospital-card {
            background: var(--card-bg);
            border-radius: 8px;
            margin-bottom: 6px;
            overflow: hidden;
        }

        .hospital-header {
            padding: 8px;
            color: white;
            font-weight: bold;
            background: var(--had-color);
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

        .night-checkbox {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .night-checkbox label {
            font-size: 0.85rem;
            color: #888;
        }

        .points-input {
            width: 60px;
            padding: 4px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
        }

        .night-input {
            display: none;
            width: 60px;
            padding: 4px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
        }

        .night-input.active {
            display: block;
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
                <i class="fas fa-hospital"></i>
                <h1>Настройка баллов HAD</h1>
            </div>
            <button class="settings-button" onclick="location.href='admin.php'">
                <i class="fas fa-arrow-left"></i>
                Назад
            </button>
        </div>

        <form id="hadSettingsForm">
            <!-- Административная работа -->
            <div class="hospital-card">
                <div class="hospital-header">Административная работа</div>
                <div class="action-row">
                    <div class="action-name" data-action="punishment">
                        Выданное взыскание (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="punishment" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="dismissal">
                        Увольнение сотрудника (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="dismissal" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="briefing">
                        Проведение брифинга (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="briefing" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="storageCheck">
                        Проверка склада (за 8 отчётов) (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="storageCheck" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="dutyControl">
                        Контроль дежурств за одну больницу (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="dutyControl" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Дежурства -->
            <div class="hospital-card">
                <div class="hospital-header">Дежурства (30 минут)</div>
                <div class="action-row">
                    <div class="action-name" data-action="dutyEls">
                        Дежурство в больнице ELSH (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="dutyEls" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="dutySandyPaleto">
                        Дежурство в Sandy Shores / Paleto Bay (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="dutySandyPaleto" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Выдача таблеток -->
            <div class="hospital-card">
                <div class="hospital-header">Выдача таблеток</div>
                <div class="action-row">
                    <div class="action-name" data-action="pillsEls">
                        Выдача таблетки в ELSH (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="pillsEls" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="pillsSandyPaleto">
                        Выдача таблетки в Sandy Shores / Paleto Bay (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="pillsSandyPaleto" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Вакцинация -->
            <div class="hospital-card">
                <div class="hospital-header">Вакцинация</div>
                <div class="action-row">
                    <div class="action-name" data-action="vaccinationEls">
                        Вакцинация в ELSH (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="vaccinationEls" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="vaccinationSandyPaleto">
                        Вакцинация в Sandy Shores / Paleto Bay (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="vaccinationSandyPaleto" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Медицинские справки -->
            <div class="hospital-card">
                <div class="hospital-header">Медицинские справки</div>
                <div class="action-row">
                    <div class="action-name" data-action="certificateEls">
                        Медицинская справка в ELSH (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="certificateEls" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="certificateSandyPaleto">
                        Медицинская справка в Sandy Shores / Paleto Bay (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="certificateSandyPaleto" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Вызовы -->
            <div class="hospital-card">
                <div class="hospital-header">Вызовы</div>
                <div class="action-row">
                    <div class="action-name" data-action="callCancel">
                        Отмена вызова (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="callCancel" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="firstAid">
                        Оказание первой медицинской помощи в городе (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="firstAid" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="firstAidNight">
                        Оказание первой медицинской помощи в ночную смену (20:00-12:00) (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="firstAidNight" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Мероприятия -->
            <div class="hospital-card">
                <div class="hospital-header">Мероприятия</div>
                <div class="action-row">
                    <div class="action-name" data-action="delivery">
                        Участие в поставке (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="delivery" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="event">
                        Участие в мероприятии (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="event" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="surgery">
                        Проведение операции (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="surgery" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="jailExamination">
                        Обследование пациента на выезде в КПЗ (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="jailExamination" min="0" step="0.5" required>
                </div>
            </div>

            <button type="submit" class="save-button">
                <i class="fas fa-save"></i>
                Сохранить изменения
            </button>
        </form>
    </div>

    <script>
        // Базовые значения для полей
        const defaultValues = {
            // Административная работа
            punishment: 5,
            dismissal: 5,
            briefing: 15,
            storageCheck: 4,
            dutyControl: 3,
            
            // Дежурства
            dutyEls: 10,
            dutySandyPaleto: 15,
            
            // Выдача таблеток
            pillsEls: 1,
            pillsSandyPaleto: 2,
            
            // Вакцинация
            vaccinationEls: 2,
            vaccinationSandyPaleto: 3,
            
            // Медицинские справки
            certificateEls: 2,
            certificateSandyPaleto: 3,
            
            // Вызовы
            callCancel: 1,
            firstAid: 4,
            firstAidNight: 5,
            
            // Мероприятия
            delivery: 20,
            event: 30,
            surgery: 30,
            jailExamination: 15
        };

        // Функция установки базовых значений
        function setDefaultValues() {
            const form = document.getElementById('hadSettingsForm');
            Object.entries(defaultValues).forEach(([key, value]) => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = value;
                }
            });
        }

        // Обновление ночных полей
        function updateNightField(baseFieldName) {
            const checkbox = document.getElementById(baseFieldName + 'NightEnabled');
            const nightInput = document.getElementById(baseFieldName + 'Night');
            const baseInput = document.querySelector(`[name="${baseFieldName}"]`);
            
            if (nightInput) {
                if (checkbox.checked) {
                    nightInput.classList.add('active');
                    const nightValue = defaultValues[baseFieldName + 'Night'];
                    if (nightValue !== undefined) {
                        nightInput.value = nightValue;
                    } else {
                        const baseValue = parseFloat(baseInput.value) || 0;
                        nightInput.value = (baseValue * 1.5).toFixed(1);
                    }
                } else {
                    nightInput.classList.remove('active');
                    nightInput.value = '';
                }
            }
        }

        // Форматирование названий действий
        function formatActionName(baseName, baseValue, nightValue = 0) {
            let text = `${baseName} (${baseValue || 'Не установлено'} `;
            if (nightValue > 0) {
                text += `/ ${nightValue} ночью`;
            }
            text += ')';
            return text;
        }

        // Обновление названий действий
        function updateActionNames(settings) {
            // Административная работа
            document.querySelector('[data-action="punishment"]').textContent = 
                formatActionName('Выданное взыскание', settings.punishment);
            document.querySelector('[data-action="dismissal"]').textContent = 
                formatActionName('Увольнение сотрудника', settings.dismissal);
            document.querySelector('[data-action="briefing"]').textContent = 
                formatActionName('Проведение брифинга', settings.briefing);
            document.querySelector('[data-action="storageCheck"]').textContent = 
                formatActionName('Проверка склада (за 8 отчётов)', settings.storageCheck);
            document.querySelector('[data-action="dutyControl"]').textContent = 
                formatActionName('Контроль дежурств за одну больницу', settings.dutyControl);

            // Дежурства
            document.querySelector('[data-action="dutyEls"]').textContent = 
                formatActionName('Дежурство в больнице ELSH', settings.dutyEls);
            document.querySelector('[data-action="dutySandyPaleto"]').textContent = 
                formatActionName('Дежурство в Sandy Shores / Paleto Bay', settings.dutySandyPaleto);

            // Выдача таблеток
            document.querySelector('[data-action="pillsEls"]').textContent = 
                formatActionName('Выдача таблетки в ELSH', settings.pillsEls);
            document.querySelector('[data-action="pillsSandyPaleto"]').textContent = 
                formatActionName('Выдача таблетки в Sandy Shores / Paleto Bay', settings.pillsSandyPaleto);

            // Вакцинация
            document.querySelector('[data-action="vaccinationEls"]').textContent = 
                formatActionName('Вакцинация в ELSH', settings.vaccinationEls);
            document.querySelector('[data-action="vaccinationSandyPaleto"]').textContent = 
                formatActionName('Вакцинация в Sandy Shores / Paleto Bay', settings.vaccinationSandyPaleto);

            // Медицинские справки
            document.querySelector('[data-action="certificateEls"]').textContent = 
                formatActionName('Медицинская справка в ELSH', settings.certificateEls);
            document.querySelector('[data-action="certificateSandyPaleto"]').textContent = 
                formatActionName('Медицинская справка в Sandy Shores / Paleto Bay', settings.certificateSandyPaleto);

            // Вызовы
            document.querySelector('[data-action="callCancel"]').textContent = 
                formatActionName('Отмена вызова', settings.callCancel);
            document.querySelector('[data-action="firstAid"]').textContent = 
                formatActionName('Оказание первой медицинской помощи в городе', settings.firstAid);
            document.querySelector('[data-action="firstAidNight"]').textContent = 
                formatActionName('Оказание первой медицинской помощи в ночную смену (20:00-12:00)', settings.firstAidNight);

            // Мероприятия
            document.querySelector('[data-action="delivery"]').textContent = 
                formatActionName('Участие в поставке', settings.delivery);
            document.querySelector('[data-action="event"]').textContent = 
                formatActionName('Участие в мероприятии', settings.event);
            document.querySelector('[data-action="surgery"]').textContent = 
                formatActionName('Проведение операции', settings.surgery);
            document.querySelector('[data-action="jailExamination"]').textContent = 
                formatActionName('Обследование пациента на выезде в КПЗ', settings.jailExamination);
        }

        // Загрузка настроек
        async function loadSettings() {
            try {
                const response = await fetch('settings_api.php?department=had', {
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        const form = document.getElementById('hadSettingsForm');
                        setDefaultValues();
                        if (data.settings) {
                            Object.entries(data.settings).forEach(([key, value]) => {
                                const input = form.querySelector(`[name="${key}"]`);
                                if (input) {
                                    input.value = value;
                                    if (key.endsWith('Night') && value > 0) {
                                        const baseField = key.replace('Night', '');
                                        const checkbox = document.getElementById(`${baseField}NightEnabled`);
                                        if (checkbox) {
                                            checkbox.checked = true;
                                            updateNightField(baseField);
                                        }
                                    }
                                }
                            });
                            updateActionNames(data.settings);
                        }
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки настроек:', error);
                setDefaultValues();
            }
        }

        // Загрузка настроек при загрузке страницы
        document.addEventListener('DOMContentLoaded', loadSettings);

        // Обработчик формы
        document.getElementById('hadSettingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {};
            const inputs = this.querySelectorAll('.points-input');
            inputs.forEach(input => {
                formData[input.name] = parseFloat(input.value) || 0;
            });

            const nightInputs = this.querySelectorAll('.night-input');
            nightInputs.forEach(input => {
                const baseField = input.name.replace('Night', '');
                const checkbox = document.getElementById(baseField + 'NightEnabled');
                formData[input.name] = checkbox.checked ? (parseFloat(input.value) || 0) : 0;
            });

            try {
                const response = await fetch('settings_api.php?department=had', {
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