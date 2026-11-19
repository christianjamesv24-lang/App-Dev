<?php
$projectRoot = __DIR__;
$composerAutoload = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
    }
}

$getEnvironmentValue = static function ($name, $default) {
    if (isset($_ENV[$name]) && is_string($_ENV[$name])) {
        return $_ENV[$name];
    }
    if (isset($_SERVER[$name]) && is_string($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    $value = getenv($name);
    return $value === false ? $default : (string) $value;
};
$secureCookies = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $secureCookies = true;
}
if ($getEnvironmentValue('RENDER', 'false') === 'true') {
    $secureCookies = true;
}

// Keep authenticated sessions protected and active for 30 minutes of inactivity.
$session_timeout = 1800;
$session_cookie_lifetime = 604800;
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => $session_cookie_lifetime,
    'path' => '/',
    'secure' => $secureCookies,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $session_timeout) {
    $_SESSION = [];
    session_destroy();
    session_start();
    session_regenerate_id(true);
}
$_SESSION['last_activity'] = time();

define('DB_HOST', $getEnvironmentValue('DB_HOST', 'localhost'));
define('DB_PORT', (int) $getEnvironmentValue('DB_PORT', '3306'));
define('DB_USER', $getEnvironmentValue('DB_USER', 'root'));
define('DB_PASS', $getEnvironmentValue('DB_PASS', ''));
define('DB_NAME', $getEnvironmentValue('DB_NAME', 'baketrack'));

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// Color Palette - Bakery Inspired
define('COLORS', [
    'primary' => '#8B6914',    // Warm Gold
    'secondary' => '#D4A574',   // Soft Caramel
    'accent' => '#F5E6D3',      // Cream
    'dark' => '#4A3520',        // Dark Brown
    'light' => '#FFF8F0',       // Light Cream
    'success' => '#4CAF50',
    'danger' => '#E74C3C',
    'warning' => '#F39C12'
]);
?>