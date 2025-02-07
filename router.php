<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Проверка маршрутизации</h2>";
echo "<pre>";
echo "URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Script: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current file: " . __FILE__ . "\n";
echo "</pre>";

// Функция для проверки файла
function checkFile($file) {
    if (file_exists($file)) {
        echo "<p>Файл $file существует</p>";
        echo "<p>Права доступа: " . substr(sprintf('%o', fileperms($file)), -4) . "</p>";
        echo "<p>Размер: " . filesize($file) . " байт</p>";
        echo "<p>Последнее изменение: " . date("Y-m-d H:i:s", filemtime($file)) . "</p>";
    } else {
        echo "<p>Файл $file не найден</p>";
    }
}

// Проверяем запрашиваемый файл
$request = $_SERVER['REQUEST_URI'];
$file = basename($request);
if ($file && $file != 'router.php') {
    echo "<h3>Проверка запрошенного файла: $file</h3>";
    checkFile($file);
}
?> 