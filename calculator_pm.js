async function calculatePoints() {
    // Логируем использование калькулятора
    try {
        await fetch('log_calculator_usage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                calculator_type: 'pm'
            })
        });
    } catch (error) {
        console.error('Ошибка логирования:', error);
    }

    // Существующий код калькулятора
    // ... existing code ...
} 