<?php
// Защита от прямого доступа к файлу
if (!defined('SECURE_ACCESS')) {
    http_response_code(403);
    die('Доступ запрещен');
}

// Хеш пароля '8saje9r60k7z2jwvxh0qhj4gto5eqw'
define('ADMIN_PASSWORD_HASH', '088ad3d599ef36aaf8823af4aa6c88f173581df8f2ec166a8f1ecb733279ee68'); 