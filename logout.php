<?php
// Определяем константу для безопасного доступа
define('SECURE_ACCESS', true);

// Запускаем сессию
session_start();

// Очищаем все данные сессии
$_SESSION = array();

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную страницу
header('Location: index.html');
exit();
?> 