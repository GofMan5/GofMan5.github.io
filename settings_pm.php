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

if ($admin['role'] !== 'HEAD PM' && $admin['role'] !== 'root') {
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
    <title>Настройка баллов PM | EMS</title>
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
            --els-color: #2980b9;
            --sandy-color: #d35400;
            --paleto-color: #8e44ad;
            --reanimation-color: #c0392b;
            --instructor-color: #27ae60;
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

        .settings-title i {
            font-size: 1.2rem;
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
            background: var(--primary-dark-color);
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
        }

        .els-header { background: var(--els-color); }
        .sandy-header { background: var(--sandy-color); }
        .paleto-header { background: var(--paleto-color); }
        .reanimation-header { background: var(--reanimation-color); }
        .instructor-header { background: var(--instructor-color); }

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

        .points-info {
            color: #888;
            font-size: 0.85rem;
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

        .hospital-card:nth-child(4),
        .hospital-card:nth-child(5) {
            grid-column: span 3;
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

        .fas {
            font-size: 0.85rem;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
        }

        /* Стилизация для input type number */
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
                <i class="fas fa-ambulance"></i>
                <h1>Настройка баллов PM</h1>
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

        <form id="pmSettingsForm">
            <!-- ELS Hospital -->
            <div class="hospital-card">
                <div class="hospital-header els-header">ELS Hospital</div>
                <div class="action-row">
                    <div class="action-name" data-action="pillsEls">
                        Выдача таблеток (Не установлено)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="pillsElsNightEnabled" onchange="updateNightField('pillsEls')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="pillsEls" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="pillsElsNight" name="pillsElsNight" min="0" step="0.5">
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="vaccinationEls">
                        Вакцинация (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="vaccinationElsNightEnabled" onchange="updateNightField('vaccinationEls')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="vaccinationEls" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="vaccinationElsNight" name="vaccinationElsNight" min="0" step="0.5">
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="certificateEls">
                        Мед. справка (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="certificateElsNightEnabled" onchange="updateNightField('certificateEls')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="certificateEls" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="certificateElsNight" name="certificateElsNight" min="0" step="0.5">
                </div>
            </div>

            <!-- Sandy Shores -->
            <div class="hospital-card">
                <div class="hospital-header sandy-header">Sandy Shores</div>
                <div class="action-row">
                    <div class="action-name" data-action="pillsSandy">
                        Выдача таблеток (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="pillsSandyNightEnabled" onchange="updateNightField('pillsSandy')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="pillsSandy" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="pillsSandyNight" name="pillsSandyNight" min="0" step="0.5">
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="vaccinationSandy">
                        Вакцинация (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="vaccinationSandyNightEnabled" onchange="updateNightField('vaccinationSandy')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="vaccinationSandy" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="vaccinationSandyNight" name="vaccinationSandyNight" min="0" step="0.5">
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="certificateSandy">
                        Мед. справка (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="certificateSandyNightEnabled" onchange="updateNightField('certificateSandy')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="certificateSandy" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="certificateSandyNight" name="certificateSandyNight" min="0" step="0.5">
                </div>
            </div>

            <!-- Paleto Bay -->
            <div class="hospital-card">
                <div class="hospital-header paleto-header">Paleto Bay</div>
                <div class="action-row">
                    <div class="action-name" data-action="pillsPaleto">
                        Выдача таблеток (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="pillsPaletoNightEnabled" onchange="updateNightField('pillsPaleto')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="pillsPaleto" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="pillsPaletoNight" name="pillsPaletoNight" min="0" step="0.5">
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="vaccinationPaleto">
                        Вакцинация (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="vaccinationPaletoNightEnabled" onchange="updateNightField('vaccinationPaleto')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="vaccinationPaleto" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="vaccinationPaletoNight" name="vaccinationPaletoNight" min="0" step="0.5">
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="certificatePaleto">
                        Мед. справка (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="certificatePaletoNightEnabled" onchange="updateNightField('certificatePaleto')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="certificatePaleto" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="certificatePaletoNight" name="certificatePaletoNight" min="0" step="0.5">
                </div>
            </div>

            <!-- Реанимация -->
            <div class="hospital-card">
                <div class="hospital-header reanimation-header">Реанимация</div>
                <div class="action-row">
                    <div class="action-name" data-action="firstAid">
                        Оказание ПМП (Не установлено ночью)
                    </div>
                    <div class="night-checkbox">
                        <input type="checkbox" id="firstAidNightEnabled" onchange="updateNightField('firstAid')">
                        <label>Ночная</label>
                    </div>
                    <input type="number" class="points-input" name="firstAid" min="0" step="0.5" required>
                    <input type="number" class="night-input" id="firstAidNight" name="firstAidNight" min="0" step="0.5">
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="rejectedCall">
                        Отклоненный вызов (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="rejectedCall" min="0" step="0.5" required>
                </div>
            </div>

            <!-- Для инструкторов -->
            <div class="hospital-card">
                <div class="hospital-header instructor-header">Для Инструкторов</div>
                <div class="action-row">
                    <div class="action-name" data-action="reportCheck">
                        Проверка отчета (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="reportCheck" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="examConduct">
                        Проведение экзамена (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="examConduct" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="hospitalCheck">
                        Проверка больниц (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="hospitalCheck" min="0" step="0.5" required>
                </div>
                <div class="action-row">
                    <div class="action-name" data-action="briefingAssistance">
                        Помощь в проведении брифинга (Не установлено)
                    </div>
                    <input type="number" class="points-input" name="briefingAssistance" min="0" step="0.5" required>
                </div>
            </div>

            <button type="submit" class="save-button">
                <i class="fas fa-save"></i>
                Сохранить изменения
            </button>
        </form>
    </div>

    <script>
        // Обновляем функцию загрузки настроек
        async function loadSettings() {
            try {
                const response = await fetch('settings_api.php?department=pm', {
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        const form = document.getElementById('pmSettingsForm');
                        
                        // Загружаем все значения из полученных настроек
                        if (data.settings) {
                            Object.entries(data.settings).forEach(([key, value]) => {
                                const input = form.querySelector(`[name="${key}"]`);
                                if (input) {
                                    input.value = value;
                                    // Если это ночное значение и оно больше 0
                                    if (key.endsWith('Night') && value > 0) {
                                        const baseField = key.replace('Night', '');
                                        const checkbox = document.getElementById(`${baseField}NightEnabled`);
                                        if (checkbox) {
                                            checkbox.checked = true;
                                            const nightInput = document.getElementById(key);
                                            if (nightInput) {
                                                nightInput.classList.add('active');
                                            }
                                        }
                                    }
                                }
                            });
                            // Обновляем названия действий с текущими значениями
                            updateActionNames(data.settings);
                        }
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки настроек:', error);
            }
        }

        // Обновляем функцию установки стандартных значений
        function setDefaultValues() {
            if (confirm('Вы уверены, что хотите установить стандартные настройки? Все текущие настройки будут заменены.')) {
                const form = document.getElementById('pmSettingsForm');
                
                // Устанавливаем стандартные значения
                const defaultValues = {
                    // ELS Hospital
                    pillsEls: 1,
                    pillsElsNight: 2,
                    vaccinationEls: 2,
                    vaccinationElsNight: 2,
                    certificateEls: 3,
                    certificateElsNight: 4,
                    
                    // Sandy Shores
                    pillsSandy: 2,
                    pillsSandyNight: 3,
                    vaccinationSandy: 3,
                    vaccinationSandyNight: 3,
                    certificateSandy: 4,
                    certificateSandyNight: 5,
                    
                    // Paleto Bay
                    pillsPaleto: 3,
                    pillsPaletoNight: 4,
                    vaccinationPaleto: 4,
                    vaccinationPaletoNight: 4,
                    certificatePaleto: 5,
                    certificatePaletoNight: 5,
                    
                    // Реанимация
                    firstAid: 3,
                    firstAidNight: 4,
                    rejectedCall: 1,
                    
                    // Для инструкторов
                    reportCheck: 10,
                    examConduct: 5,
                    hospitalCheck: 5,
                    briefingAssistance: 15
                };

                // Устанавливаем значения и обновляем чекбоксы
                Object.entries(defaultValues).forEach(([key, value]) => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = value;
                        if (key.endsWith('Night')) {
                            const baseField = key.replace('Night', '');
                            const checkbox = document.getElementById(`${baseField}NightEnabled`);
                            if (checkbox) {
                                checkbox.checked = true;
                                const nightInput = document.getElementById(key);
                                if (nightInput) {
                                    nightInput.classList.add('active');
                                }
                            }
                        }
                    }
                });

                // Обновляем отображение названий
                updateActionNames(defaultValues);
            }
        }

        // Обновляем функцию для работы с ночными полями
        function updateNightField(baseFieldName) {
            const checkbox = document.getElementById(baseFieldName + 'NightEnabled');
            const nightInput = document.getElementById(baseFieldName + 'Night');
            const baseInput = document.querySelector(`[name="${baseFieldName}"]`);
            
            if (nightInput) {
                if (checkbox.checked) {
                    nightInput.classList.add('active');
                    if (nightInput.value === '') {
                        // Если значение пустое, устанавливаем значение как 1.5 от дневного
                        const baseValue = parseFloat(baseInput.value) || 0;
                        nightInput.value = (baseValue * 1.5).toFixed(1);
                    }
                } else {
                    nightInput.classList.remove('active');
                    nightInput.value = '0';
                }
            }
        }

        // Добавляем функцию для форматирования текста действия
        function formatActionName(baseName, baseValue, nightValue = 0) {
            let text = `${baseName} (${baseValue || 'Не установлено'} `;
            if (nightValue > 0) {
                text += `/ ${nightValue} ночью`;
            }
            text += ')';
            return text;
        }

        // Функция обновления названий действий
        function updateActionNames(settings) {
            // ELS Hospital
            document.querySelector('[data-action="pillsEls"]').textContent = 
                formatActionName('Выдача таблеток', settings.pillsEls, settings.pillsElsNight);
            document.querySelector('[data-action="vaccinationEls"]').textContent = 
                formatActionName('Вакцинация', settings.vaccinationEls, settings.vaccinationElsNight);
            document.querySelector('[data-action="certificateEls"]').textContent = 
                formatActionName('Мед. справка', settings.certificateEls, settings.certificateElsNight);

            // Sandy Shores
            document.querySelector('[data-action="pillsSandy"]').textContent = 
                formatActionName('Выдача таблеток', settings.pillsSandy, settings.pillsSandyNight);
            document.querySelector('[data-action="vaccinationSandy"]').textContent = 
                formatActionName('Вакцинация', settings.vaccinationSandy, settings.vaccinationSandyNight);
            document.querySelector('[data-action="certificateSandy"]').textContent = 
                formatActionName('Мед. справка', settings.certificateSandy, settings.certificateSandyNight);

            // Paleto Bay
            document.querySelector('[data-action="pillsPaleto"]').textContent = 
                formatActionName('Выдача таблеток', settings.pillsPaleto, settings.pillsPaletoNight);
            document.querySelector('[data-action="vaccinationPaleto"]').textContent = 
                formatActionName('Вакцинация', settings.vaccinationPaleto, settings.vaccinationPaletoNight);
            document.querySelector('[data-action="certificatePaleto"]').textContent = 
                formatActionName('Мед. справка', settings.certificatePaleto, settings.certificatePaletoNight);

            // Реанимация
            document.querySelector('[data-action="firstAid"]').textContent = 
                formatActionName('Оказание ПМП', settings.firstAid, settings.firstAidNight);
            document.querySelector('[data-action="rejectedCall"]').textContent = 
                formatActionName('Отклоненный вызов', settings.rejectedCall);

            // Инструкторы
            document.querySelector('[data-action="reportCheck"]').textContent = 
                formatActionName('Проверка отчета', settings.reportCheck);
            document.querySelector('[data-action="examConduct"]').textContent = 
                formatActionName('Проведение экзамена', settings.examConduct);
            document.querySelector('[data-action="hospitalCheck"]').textContent = 
                formatActionName('Проверка больниц', settings.hospitalCheck);
            document.querySelector('[data-action="briefingAssistance"]').textContent = 
                formatActionName('Помощь в проведении брифинга', settings.briefingAssistance);
        }

        // Загружаем настройки при загрузке страницы
        document.addEventListener('DOMContentLoaded', loadSettings);

        // Обработчик формы
        document.getElementById('pmSettingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {};
            // Сначала добавляем все базовые поля
            const inputs = this.querySelectorAll('.points-input');
            inputs.forEach(input => {
                formData[input.name] = parseFloat(input.value) || 0;
            });

            // Добавляем ночные поля
            const nightInputs = this.querySelectorAll('.night-input');
            nightInputs.forEach(input => {
                const baseField = input.name.replace('Night', '');
                const checkbox = document.getElementById(baseField + 'NightEnabled');
                formData[input.name] = checkbox.checked ? (parseFloat(input.value) || 0) : 0;
            });

            try {
                const response = await fetch('settings_api.php?department=pm', {
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
                        // Обновляем страницу после сохранения
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