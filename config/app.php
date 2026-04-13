<?php
// config/app.php — Central app configuration
// Uses environment variable on Render, falls back to XAMPP local path
$baseUrl = getenv('BASE_URL');
define('BASE_URL', $baseUrl !== false ? $baseUrl : '/SPORTIFY');
define('APP_NAME', 'Sportify');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('MAX_PHOTO_SIZE', 5 * 1024 * 1024); // 5 MB
define('SESSION_TIMEOUT', 7200); // 2 hours
