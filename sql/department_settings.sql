-- Создание таблицы настроек отделов
CREATE TABLE IF NOT EXISTS department_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(10) NOT NULL,
    settings JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавляем значения по умолчанию для PM
INSERT INTO department_settings (department, settings) VALUES 
('pm', '{
    "reanimation": 3,
    "firstAid": 3,
    "firstAidNight": 4,
    "rejectedCall": 1,
    
    "pillsEls": 1,
    "pillsElsNight": 2,
    "pillsSandy": 2,
    "pillsSandyNight": 3,
    "pillsPaleto": 3,
    "pillsPaletoNight": 4,
    
    "vaccinationEls": 2,
    "vaccinationSandy": 3,
    "vaccinationPaleto": 4,
    
    "certificateEls": 3,
    "certificateElsNight": 4,
    "certificateSandy": 4,
    "certificateSandyNight": 5,
    "certificatePaleto": 5,
    "certificatePaletoNight": 6,
    
    "reportCheck": 10,
    "examConduct": 5,
    "hospitalCheck": 5,
    "briefingAssistance": 15
}')
ON DUPLICATE KEY UPDATE settings = VALUES(settings);

-- Создание таблицы посещений
CREATE TABLE IF NOT EXISTS site_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    page VARCHAR(50) NOT NULL,
    INDEX idx_visitor_ip (visitor_ip),
    INDEX idx_visited_at (visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 