// Функция для показа уведомлений
function showNotification(message, type = 'info', duration = 3000) {
    // Удаляем предыдущее уведомление, если оно есть
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Создаем новое уведомление
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // Выбираем иконку в зависимости от типа
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';

    notification.innerHTML = `
        <div class="icon">
            <i class="fas fa-${icon}"></i>
        </div>
        <p class="message">${message}</p>
        <button class="close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Добавляем уведомление на страницу
    document.body.appendChild(notification);
    
    // Показываем уведомление с анимацией
    setTimeout(() => notification.classList.add('show'), 10);

    // Автоматически скрываем через указанное время
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
}

// Функция для показа модального окна
function showModal(modal) {
    const overlay = document.querySelector('.modal-overlay');
    if (!overlay) {
        const newOverlay = document.createElement('div');
        newOverlay.className = 'modal-overlay';
        document.body.appendChild(newOverlay);
    }
    
    document.body.classList.add('modal-open');
    
    if (overlay) {
        overlay.style.display = 'block';
    }
    if (modal) {
        modal.style.display = 'block';
        
        // Запускаем анимацию
        requestAnimationFrame(() => {
            if (overlay) overlay.classList.add('active');
            modal.classList.add('active');
        });
    }
}

// Функция для скрытия модального окна
function hideModal(modal) {
    const overlay = document.querySelector('.modal-overlay');
    document.body.classList.remove('modal-open');
    
    if (overlay) {
        overlay.classList.remove('active');
    }
    if (modal) {
        modal.classList.remove('active');
    }
    
    // Ждем окончания анимации
    setTimeout(() => {
        if (overlay) overlay.style.display = 'none';
        if (modal) modal.style.display = 'none';
    }, 300);
}

// Глобальные функции для работы с модальным окном
window.uploadToImgur = async function(blob) {
    const loadingModal = document.getElementById('uploadModal');
    try {
        console.log('Начинаем загрузку...');
        
        // Показываем индикатор загрузки
        showModal(loadingModal);
        loadingModal.innerHTML = `
            <div class="modal-content" style="
                background: var(--card-bg);
                border-radius: 12px;
                padding: 20px;
                max-width: 400px;
                width: 100%;
            ">
                <div class="modal-header" style="
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 20px;
                ">
                    <div style="
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        background: rgba(46, 204, 113, 0.1);
                        padding: 8px 15px;
                        border-radius: 30px;
                        border: 1px solid rgba(46, 204, 113, 0.2);
                    ">
                        <i class="fas fa-spinner fa-spin" style="
                            color: #3498db;
                            font-size: 1rem;
                        "></i>
                        <span style="
                            color: var(--text-color);
                            font-size: 0.95rem;
                            font-weight: 500;
                        ">Загрузка...</span>
                </div>
                </div>
                <div class="modal-body">
                    <div class="link-container" style="
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                    ">
                        <input type="text" 
                            id="imageLink" 
                            value="Генерация ссылки..." 
                            readonly 
                            style="
                                width: 100%;
                                padding: 10px 12px;
                                border: 1px solid var(--border-color);
                                border-radius: 8px;
                                background: var(--input-bg);
                                color: var(--text-color);
                                opacity: 0.7;
                                font-size: 0.9rem;
                            "
                        >
                        <button disabled style="
                            width: 100%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 8px;
                            padding: 10px 15px;
                            border: none;
                            border-radius: 8px;
                            background: #3498db;
                            color: white;
                            cursor: not-allowed;
                            font-size: 0.9rem;
                            transition: all 0.2s ease;
                            height: 40px;
                            opacity: 0.7;
                        ">
                            <i class="fas fa-spinner fa-spin"></i>
                            Загрузка...
                        </button>
                    </div>
                </div>
            </div>`;

        // Добавляем эффект при наведении на кнопку закрытия
        const closeButton = loadingModal.querySelector('.close-button');
        if (closeButton) {
            closeButton.addEventListener('mouseenter', () => {
                closeButton.style.opacity = '1';
            });
            closeButton.addEventListener('mouseleave', () => {
                closeButton.style.opacity = '0.7';
            });
        }

    } catch (error) {
        console.error('Подробная ошибка:', error);
        loadingModal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header" style="
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 15px;
                    background: var(--card-bg);
                    padding: 15px;
                    border-radius: 8px;
                ">
                    <i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i>
                    <h3 style="
                        margin: 0;
                        color: var(--text-color);
                        font-size: 1.1rem;
                        font-weight: 500;
                    ">Ошибка!</h3>
                    <button class="close-button" onclick="window.closeModal()" style="
                        margin-left: auto;
                        background: none;
                        border: none;
                        color: var(--text-color);
                        cursor: pointer;
                        padding: 5px;
                        font-size: 1.1rem;
                    ">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p style="
                        color: var(--text-color);
                        margin-bottom: 10px;
                    ">Произошла ошибка при загрузке скриншота:</p>
                    <pre style="
                        color: #e74c3c;
                        margin: 10px 0;
                        padding: 10px;
                        background: rgba(231, 76, 60, 0.1);
                        border-radius: 6px;
                        font-family: monospace;
                        white-space: pre-wrap;
                        word-break: break-all;
                    ">${error.message}</pre>
                    <p style="
                        color: var(--text-color);
                        font-size: 0.9rem;
                        opacity: 0.8;
                    ">Проверьте консоль разработчика (F12) для дополнительной информации.</p>
                </div>
            </div>`;
    }
}

window.copyLink = function() {
    const linkInput = document.getElementById('imageLink');
    linkInput.select();
    document.execCommand('copy');
    showNotification('Ссылка скопирована в буфер обмена!', 'success');
}

window.closeModal = function() {
    const modal = document.getElementById('uploadModal');
    hideModal(modal);
}

// Объект с баллами за услуги
const servicePoints = {
    els_1: 1,  // Выдача таблеток ELS
    els_2: 2,  // Вакцинация ELS
    els_3: 3,  // Мед. справка ELS
    ss_1: 2,   // Выдача таблеток Sandy Shores
    ss_2: 3,   // Вакцинация Sandy Shores
    ss_3: 4,   // Мед. справка Sandy Shores
    pb_1: 3,   // Выдача таблеток Paleto Bay
    pb_2: 4,   // Вакцинация Paleto Bay
    pb_3: 5,   // Мед. справка Paleto Bay
    r_1: 3,    // Оказание ПМП
    r_2: 1,    // Откаченный вызов
    i_1: 10,   // Проверка отчёта
    i_2: 5,    // Проведение экзамена
    i_3: 5,    // Проверка больниц
    i_4: 15    // Помощь в проведении брифинга
};

// Обновляем объект с баллами за ночные смены (НЕ МЕНЯТЬ ЗНАЧЕНИЯ НИ ЗА ЧТО)
const nightShiftPoints = {
    'els_1': 2,  // Выдача таблеток ELS
    'els_3': 4,  // Мед. справка ELS
    'ss_1': 4,   // Выдача таблеток Sandy Shores
    'ss_3': 5,   // Мед. справка Sandy Shores
    'pb_1': 4,   // Выдача таблеток Paleto Bay
    'pb_3': 5,   // Мед. справка Paleto Bay
    'r_1': 4     // Оказание ПМП ночью
};

// Функция для создания ночной строки
function createNightService(parentElement, serviceName, points) {
    const nightService = document.createElement('div');
    nightService.className = 'night-service';
    nightService.style.display = 'none';
    
    const label = document.createElement('label');
    label.textContent = `Ночная смена (${points} балла)`;
    
    const inputGroup = document.createElement('div');
    inputGroup.className = 'input-group';
    
    const input = document.createElement('input');
    input.type = 'number';
    input.min = '0';
    input.className = 'night-input';
    input.name = `night_service_${serviceName}`;
    input.disabled = true;
    
    const pointsSpan = document.createElement('span');
    pointsSpan.className = 'points';
    pointsSpan.textContent = '0 баллов';
    
    inputGroup.appendChild(input);
    inputGroup.appendChild(pointsSpan);
    nightService.appendChild(label);
    nightService.appendChild(inputGroup);
    
    parentElement.appendChild(nightService);
    return nightService;
}

// Функция для подсчета баллов
function calculatePoints() {
    let grandTotal = 0;
    
    // Получаем все секции больниц
    const hospitalSections = document.querySelectorAll('.hospital-section');
    
    hospitalSections.forEach(section => {
        let sectionTotal = 0;
        
        // Подсчет обычных услуг
        section.querySelectorAll('.service-line').forEach(serviceLine => {
            const input = serviceLine.querySelector('.service-input');
            const serviceName = input.name.replace('service_', '');
            const value = parseInt(input.value) || 0;
            const points = servicePoints[serviceName];
            const total = value * points;
            
            // Обновляем отображение баллов
            const pointsSpan = serviceLine.querySelector('.points');
            if (pointsSpan) {
                pointsSpan.textContent = `${total} баллов`;
            }
            
            sectionTotal += total;
            
            // Проверяем наличие ночной смены
            const nightCheckbox = serviceLine.querySelector('.night-shift');
            if (nightCheckbox && nightCheckbox.checked) {
                const nightService = section.querySelector(`.night-service[data-service="${serviceName}"]`);
                if (nightService) {
                    const nightInput = nightService.querySelector('input');
                    const nightValue = parseInt(nightInput.value) || 0;
                    const nightPoints = nightShiftPoints[serviceName];
                    const nightTotal = nightValue * nightPoints;
                    
                    // Обновляем отображение баллов ночной смены
                    const nightPointsSpan = nightService.querySelector('.points');
                    if (nightPointsSpan) {
                        nightPointsSpan.textContent = `${nightTotal} баллов`;
                    }
                    
                    sectionTotal += nightTotal;
                }
            }
        });
        
        // Обновляем итог секции
        const sectionTotalSpan = section.querySelector('.total span');
        if (sectionTotalSpan) {
            sectionTotalSpan.textContent = sectionTotal;
        }
        
        grandTotal += sectionTotal;
    });
    
    // Обновляем общий итог
    const grandTotalSpan = document.querySelector('.grand-total span');
    if (grandTotalSpan) {
        grandTotalSpan.textContent = grandTotal;
    }
}

// Функция получения класса секции
function getSectionClass(section) {
    const sectionClasses = {
        'els': 'els-hospital',
        'ss': 'sandy-shores',
        'pb': 'paleto-bay',
        'r': 'reanimation-section',
        'i': 'instructor-section'
    };
    return sectionClasses[section];
}

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация настроек
    const settings = {
        snowflakes: true,
        darkTheme: true,
        animations: true
    };

    // Загрузка сохраненных настроек или использование значений по умолчанию
    const savedSettings = localStorage.getItem('siteSettings');
    if (savedSettings) {
        const parsed = JSON.parse(savedSettings);
        // Используем значения из localStorage только если они есть, иначе оставляем значения по умолчанию
        settings.snowflakes = parsed.snowflakes !== undefined ? parsed.snowflakes : true;
        settings.darkTheme = parsed.darkTheme !== undefined ? parsed.darkTheme : true;
        settings.animations = parsed.animations !== undefined ? parsed.animations : true;
    }

    // Функция для сохранения настроек
    function saveSettings() {
        localStorage.setItem('siteSettings', JSON.stringify(settings));
    }

    // Функция для создания снежинок
    function createSnowflake() {
        const snowflake = document.createElement('div');
        snowflake.className = 'snowflake';
        
        // Разные символы снежинок
        const snowflakes = ['❅', '❆', '❄', '❉', '❊'];
        snowflake.innerHTML = snowflakes[Math.floor(Math.random() * snowflakes.length)];
        
        // Случайная начальная позиция
        snowflake.style.left = Math.random() * 100 + 'vw';
        snowflake.style.opacity = Math.random() * 0.7 + 0.3;
        
        // Случайное начальное смещение
        const startOffset = (Math.random() * 10 - 5) + 'px';
        snowflake.style.transform = `translateX(${startOffset})`;
        
        document.body.appendChild(snowflake);

        // Удаляем снежинку после окончания анимации
        snowflake.addEventListener('animationend', () => {
            snowflake.remove();
        });
    }

    // Интервал создания снежинок
    let snowflakeInterval;

    // Функция управления снежинками
    function toggleSnowflakes(enabled) {
        if (enabled) {
            // Создаем начальное количество снежинок
            for(let i = 0; i < 15; i++) {
                setTimeout(() => createSnowflake(), Math.random() * 2000);
            }
            snowflakeInterval = setInterval(createSnowflake, 300);
        } else {
            clearInterval(snowflakeInterval);
            document.querySelectorAll('.snowflake').forEach(s => {
                s.style.animation = 'none';
                s.style.opacity = '0';
                setTimeout(() => s.remove(), 1000);
            });
        }
    }

    // Функция управления темой
    function toggleDarkTheme(enabled) {
        document.body.classList.toggle('light-theme', !enabled);
        settings.darkTheme = enabled;
        saveSettings();
    }

    // Функция управления анимациями
    function toggleAnimations(enabled) {
        document.body.classList.toggle('no-animations', !enabled);
        settings.animations = enabled;
        saveSettings();
    }

    // Инициализация переключателей
    const snowflakesToggle = document.getElementById('snowflakes-toggle');
    const darkThemeToggle = document.getElementById('dark-theme-toggle');
    const animationsToggle = document.getElementById('animations-toggle');

    // Установка начальных состояний
    snowflakesToggle.checked = settings.snowflakes;
    darkThemeToggle.checked = settings.darkTheme;
    animationsToggle.checked = settings.animations;

    // Применение начальных настроек
    toggleSnowflakes(settings.snowflakes);
    toggleDarkTheme(settings.darkTheme);
    toggleAnimations(settings.animations);

    // Обработчики событий
    snowflakesToggle.addEventListener('change', (e) => {
        settings.snowflakes = e.target.checked;
        toggleSnowflakes(e.target.checked);
        saveSettings();
    });

    darkThemeToggle.addEventListener('change', (e) => {
        toggleDarkTheme(e.target.checked);
    });

    animationsToggle.addEventListener('change', (e) => {
        toggleAnimations(e.target.checked);
    });

    // Обработчик для кнопки скриншота
    const screenshotButton = document.querySelector('.screenshot-button');
    if (screenshotButton) {
        screenshotButton.addEventListener('click', takeScreenshot);
    }

    // Показ/скрытие меню настроек по клику
    const settingsButton = document.querySelector('.settings-button');
    const settingsMenu = document.querySelector('.settings-menu');
    
    if (settingsButton && settingsMenu) {
        settingsButton.addEventListener('click', (e) => {
            e.stopPropagation();
            settingsMenu.classList.toggle('visible');
            settingsButton.classList.toggle('active');
            
            // Добавляем звуковой эффект при открытии/закрытии меню
            const audio = new Audio('data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA/+M4wAAAAAAAAAAAAEluZm8AAAAPAAAAAwAAABQADw8PDw8PDw8PDw8PDw8PDw8PDw8PDw8VFRUVFRUVFRUVFRUVFRUVFRUVFRUVFR4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eJCQkJCQkJCQkJCQkJCQkJCQkJCQkJCQAAAAOTGF2ZjU4LjEyLjEwMAAAAAAAAAAAAAAA/+MYxAAAAANIAAAAAExBTUUzLjEwMFVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV/+MYxDsAAANIAAAAAFVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV');
            audio.volume = 0.2;
            audio.play().catch(() => {}); // Игнорируем ошибки воспроизведения
        });

        // Закрытие меню при клике вне его
        document.addEventListener('click', (e) => {
            if (!settingsMenu.contains(e.target) && !settingsButton.contains(e.target)) {
                settingsMenu.classList.remove('visible');
                settingsButton.classList.remove('active');
            }
        });

        // Добавляем эффект при наведении на пункты меню
        const settingsItems = document.querySelectorAll('.settings-item');
        settingsItems.forEach(item => {
            item.addEventListener('mouseenter', () => {
                if (!document.body.classList.contains('no-animations')) {
                    item.style.transform = 'translateX(5px)';
                }
            });
            
            item.addEventListener('mouseleave', () => {
                if (!document.body.classList.contains('no-animations')) {
                    item.style.transform = 'translateX(0)';
                }
            });
        });
    }

    // Добавляем обработчики для всех инпутов
    document.querySelectorAll('.service-input, .night-input').forEach(input => {
        input.addEventListener('input', calculatePoints);
    });
    
    // Добавляем обработчики для чекбоксов ночной смены
    document.querySelectorAll('.night-shift').forEach(checkbox => {
        checkbox.addEventListener('change', calculatePoints);
    });
    
    // Первоначальный подсчет
    calculatePoints();

    // Инициализация ночных строк
    initializeNightShifts();

    // Добавляем оверлей если его нет
    if (!document.querySelector('.modal-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        document.body.appendChild(overlay);
    }
});

// Обновляем инициализацию ночных смен
function initializeNightShifts() {
    document.querySelectorAll('.night-checkbox-wrapper input[type="checkbox"]').forEach(checkbox => {
        const serviceName = checkbox.name.replace('night_shift_', '');
        const serviceRow = checkbox.closest('.service-line');
        
        if (serviceRow && nightShiftPoints[serviceName]) {
            let nightService = serviceRow.parentElement.querySelector(`.night-service[data-service="${serviceName}"]`);
            
            if (!nightService) {
                nightService = document.createElement('div');
                nightService.className = 'night-service';
                nightService.setAttribute('data-service', serviceName);
                
                const label = document.createElement('label');
                label.textContent = `Ночная смена (${nightShiftPoints[serviceName]} баллов)`;
                
                const inputGroup = document.createElement('div');
                inputGroup.className = 'input-group';
                
                const input = document.createElement('input');
                input.type = 'number';
                input.min = '0';
                input.className = 'service-input night-input';
                input.name = `night_service_${serviceName}`;
                input.disabled = !checkbox.checked;
                
                const pointsSpan = document.createElement('span');
                pointsSpan.className = 'points';
                pointsSpan.textContent = '0 баллов';
                
                inputGroup.appendChild(input);
                inputGroup.appendChild(pointsSpan);
                nightService.appendChild(label);
                nightService.appendChild(inputGroup);
                
                serviceRow.parentElement.insertBefore(nightService, serviceRow.nextSibling);
                
                input.addEventListener('input', calculatePoints);
            }
            
            checkbox.addEventListener('change', () => {
                const input = nightService.querySelector('input');
                input.disabled = !checkbox.checked;
                
                if (checkbox.checked) {
                    requestAnimationFrame(() => {
                        nightService.classList.add('visible');
                    });
                } else {
                    nightService.classList.remove('visible');
                    input.value = '';
                    calculatePoints();
                }
            });
        }
    });
}

// Функция для создания скриншота
async function takeScreenshot() {
    try {
        showNotification('Создание скриншота...', 'info');

        // Получаем основной контейнер
        const container = document.querySelector('.container');
        
        // Сохраняем оригинальные стили контейнера
        const originalStyles = {
            width: container.style.width,
            maxWidth: container.style.maxWidth,
            overflow: container.style.overflow,
            position: container.style.position
        };

        // Временно изменяем стили контейнера для захвата полного содержимого
        container.style.width = 'auto';
        container.style.maxWidth = 'none';
        container.style.overflow = 'visible';
        container.style.position = 'relative';
        
        // Временно скрываем элементы
        const elementsToHide = document.querySelectorAll('.snowflake, .settings-menu, .notification, .modal, .modal-overlay');
        elementsToHide.forEach(el => el.style.display = 'none');

        // Создаем глубокую копию контейнера
        const clone = container.cloneNode(true);
        
        // Копируем вычисленные стили для каждого элемента
        function copyComputedStyles(source, target) {
            const sourceStyles = window.getComputedStyle(source);
            const targetStyles = target.style;
            
            for (let key of sourceStyles) {
                targetStyles[key] = sourceStyles[key];
            }

            for (let i = 0; i < source.children.length; i++) {
                if (source.children[i] && target.children[i]) {
                    copyComputedStyles(source.children[i], target.children[i]);
                }
            }
        }

        // Применяем стили к клону
        copyComputedStyles(container, clone);
        
        // Позиционируем клон
        clone.style.position = 'fixed';
        clone.style.top = '0';
        clone.style.left = '0';
        clone.style.zIndex = '-1';
        clone.style.width = 'auto';
        clone.style.maxWidth = 'none';
        clone.style.padding = '20px';
        clone.style.background = document.body.classList.contains('light-theme') ? '#f5f6f7' : '#18191a';
        clone.style.overflow = 'visible';
        
        document.body.appendChild(clone);

        // Получаем реальные размеры всего содержимого
        const bounds = clone.getBoundingClientRect();
        const width = Math.ceil(bounds.width);
        const height = Math.ceil(bounds.height);

        // Ждем загрузку всех стилей и изображений
        await new Promise(resolve => setTimeout(resolve, 300));
        
        try {
            // Создаем скриншот с оптимизированными настройками
            const dataUrl = await domtoimage.toPng(clone, {
                quality: 0.95,
                bgcolor: document.body.classList.contains('light-theme') ? '#f5f6f7' : '#18191a',
                height: height,
                width: width,
                style: {
                    'transform': 'none',
                    'box-shadow': 'none'
                },
                imagePlaceholder: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
                filter: (node) => {
                    return node.tagName !== 'i' && !node.classList?.contains('snowflake');
                }
            });

            // Удаляем клон и восстанавливаем стили
            document.body.removeChild(clone);
            Object.assign(container.style, originalStyles);
            elementsToHide.forEach(el => el.style.display = '');

            // Конвертируем в blob с оптимизированным размером
            const response = await fetch(dataUrl);
            const blob = await response.blob();

            // Проверяем размер файла
            if (blob.size > 10 * 1024 * 1024) { // Если больше 10MB
                throw new Error('Размер скриншота слишком большой');
            }

            // Показываем модальное окно загрузки
            const loadingModal = document.getElementById('uploadModal');
            showModal(loadingModal);

            try {
                // Пробуем загрузить на Imgur
                const formData = new FormData();
                formData.append('image', blob);

                const imgurResponse = await fetch('https://api.imgur.com/3/image', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Client-ID a22acfa73897123'
                    },
                    body: formData
                });

                if (!imgurResponse.ok) {
                    throw new Error('Ошибка ответа от сервера Imgur');
                }

                const data = await imgurResponse.json();
                
                if (!data.success) {
                    throw new Error(data.data.error || 'Ошибка при загрузке изображения');
                }

                showUploadSuccess(loadingModal, data.data.link);
            } catch (imgurError) {
                console.error('Ошибка загрузки на Imgur:', imgurError);
                
                // Если загрузка на Imgur не удалась, предлагаем скачать файл
                const downloadLink = document.createElement('a');
                downloadLink.href = dataUrl;
                downloadLink.download = 'calculator_screenshot.png';
                
                loadingModal.innerHTML = `
                    <div class="modal-content" style="
                        background: var(--card-bg);
                        border-radius: 12px;
                        padding: 20px;
                        max-width: 400px;
                        width: 100%;
                    ">
                        <div class="modal-header" style="
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            margin-bottom: 20px;
                        ">
                            <div style="
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                background: rgba(243, 156, 18, 0.1);
                                padding: 8px 15px;
                                border-radius: 30px;
                                border: 1px solid rgba(243, 156, 18, 0.2);
                            ">
                                <i class="fas fa-exclamation-triangle" style="
                                    color: #f39c12;
                                    font-size: 1rem;
                                "></i>
                                <span style="
                                    color: #f39c12;
                                    font-size: 0.95rem;
                                    font-weight: 500;
                                ">Сервис Imgur недоступен</span>
                            </div>
                            <button class="close-button" onclick="window.closeModal()" style="
                                background: none;
                                border: none;
                                color: var(--text-color);
                                cursor: pointer;
                                padding: 8px;
                                font-size: 1.1rem;
                                opacity: 0.7;
                                transition: opacity 0.2s;
                            ">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p style="
                                color: var(--text-color);
                                margin-bottom: 15px;
                                font-size: 0.9rem;
                            ">К сожалению, сервис Imgur временно недоступен. Вы можете скачать скриншот напрямую:</p>
                            <button id="downloadButton" style="
                                width: 100%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 8px;
                                padding: 10px 15px;
                                border: none;
                                border-radius: 8px;
                                background: #3498db;
                                color: white;
                                cursor: pointer;
                                font-size: 0.9rem;
                                transition: all 0.2s ease;
                                height: 40px;
                            ">
                                <i class="fas fa-download"></i>
                                Скачать скриншот
                            </button>
                        </div>
                    </div>`;

                const downloadButton = loadingModal.querySelector('#downloadButton');
                downloadButton.addEventListener('click', () => {
                    downloadLink.click();
                    showNotification('Скриншот сохранен!', 'success');
                });

                // Добавляем эффекты при наведении
                downloadButton.addEventListener('mouseenter', () => {
                    downloadButton.style.background = '#2980b9';
                    downloadButton.style.transform = 'translateY(-1px)';
                });

                downloadButton.addEventListener('mouseleave', () => {
                    downloadButton.style.background = '#3498db';
                    downloadButton.style.transform = 'translateY(0)';
                });
            }

        } catch (error) {
            console.error('Ошибка создания скриншота:', error);
            showNotification('Ошибка при создании скриншота: ' + error.message, 'error');
            const loadingModal = document.getElementById('uploadModal');
            if (loadingModal) {
                hideModal(loadingModal);
            }
        }

    } catch (error) {
        console.error('Ошибка создания скриншота:', error);
        showNotification('Ошибка при создании скриншота: ' + error.message, 'error');
        
        // Восстанавливаем видимость элементов в случае ошибки
        document.querySelectorAll('.snowflake, .settings-menu, .notification, .modal, .modal-overlay')
            .forEach(el => el.style.display = '');
    }
}

// Функция для отображения успешной загрузки
function showUploadSuccess(modal, imageUrl) {
    modal.innerHTML = `
        <div class="modal-content" style="
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            max-width: 400px;
            width: 100%;
        ">
            <div class="modal-header" style="
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 20px;
            ">
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    background: rgba(46, 204, 113, 0.1);
                    padding: 8px 15px;
                    border-radius: 30px;
                    border: 1px solid rgba(46, 204, 113, 0.2);
                ">
                    <i class="fas fa-check-circle" style="
                        color: #2ecc71;
                        font-size: 1rem;
                    "></i>
                    <span style="
                        color: #2ecc71;
                        font-size: 0.95rem;
                        font-weight: 500;
                    ">Скриншот загружен</span>
                </div>
                <button class="close-button" onclick="window.closeModal()" style="
                    background: none;
                    border: none;
                    color: var(--text-color);
                    cursor: pointer;
                    padding: 8px;
                    font-size: 1.1rem;
                    opacity: 0.7;
                    transition: opacity 0.2s;
                ">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="link-container" style="
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                ">
                    <input type="text" 
                        id="imageLink" 
                        value="${imageUrl}" 
                        readonly 
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid var(--border-color);
                            border-radius: 8px;
                            background: var(--input-bg);
                            color: var(--text-color);
                            font-size: 0.9rem;
                        "
                    >
                    <button id="copyButton" style="
                        width: 100%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        padding: 10px 15px;
                        border: none;
                        border-radius: 8px;
                        background: #3498db;
                        color: white;
                        cursor: pointer;
                        font-size: 0.9rem;
                        transition: all 0.2s ease;
                        height: 40px;
                        opacity: 1;
                    ">
                        <i class="fas fa-copy"></i>
                        Копировать
                    </button>
                </div>
            </div>
        </div>`;

    const copyButton = modal.querySelector('#copyButton');
    const imageLink = modal.querySelector('#imageLink');

    copyButton.addEventListener('click', () => {
        imageLink.select();
        document.execCommand('copy');
        showNotification('Ссылка скопирована в буфер обмена!', 'success');
        copyButton.style.background = '#2980b9';
        setTimeout(() => {
            copyButton.style.background = '#3498db';
        }, 200);
    });

    copyButton.addEventListener('mouseenter', () => {
        copyButton.style.background = '#2980b9';
        copyButton.style.transform = 'translateY(-1px)';
    });

    copyButton.addEventListener('mouseleave', () => {
        copyButton.style.background = '#3498db';
        copyButton.style.transform = 'translateY(0)';
    });
}
