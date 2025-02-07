document.addEventListener('DOMContentLoaded', () => {
    // Добавляем анимацию появления карточек
    const cards = document.querySelectorAll('.tool-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });

    // Обработка наведения на карточки
    cards.forEach(card => {
        if (!card.classList.contains('coming-soon')) {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        }
    });

    // Предотвращаем переход по ссылкам для карточек "Скоро"
    document.querySelectorAll('.coming-soon').forEach(card => {
        card.addEventListener('click', (e) => {
            e.preventDefault();
            // Можно добавить уведомление или подсказку
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = 'Этот инструмент скоро появится!';
            tooltip.style.position = 'absolute';
            tooltip.style.background = '#333';
            tooltip.style.color = 'white';
            tooltip.style.padding = '8px 12px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.fontSize = '14px';
            tooltip.style.zIndex = '1000';
            tooltip.style.opacity = '0';
            tooltip.style.transition = 'opacity 0.3s ease';

            card.appendChild(tooltip);
            
            // Позиционируем подсказку
            const rect = card.getBoundingClientRect();
            tooltip.style.top = `${rect.height + 10}px`;
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translateX(-50%)';

            // Показываем подсказку
            setTimeout(() => tooltip.style.opacity = '1', 10);

            // Удаляем подсказку через 2 секунды
            setTimeout(() => {
                tooltip.style.opacity = '0';
                setTimeout(() => tooltip.remove(), 300);
            }, 2000);
        });
    });
}); 