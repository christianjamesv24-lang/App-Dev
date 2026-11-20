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
if ($getEnvironmentValue('VERCEL', 'false') === '1' || $getEnvironmentValue('VERCEL_ENV', '') === 'production') {
    $secureCookies = true;
}

define('DB_HOST', $getEnvironmentValue('DB_HOST', 'localhost'));
define('DB_PORT', (int) $getEnvironmentValue('DB_PORT', '3306'));
define('DB_USER', $getEnvironmentValue('DB_USER', 'root'));
define('DB_PASS', $getEnvironmentValue('DB_PASS', ''));
define('DB_NAME', $getEnvironmentValue('DB_NAME', 'baketrack'));

$dbSslEnabled = filter_var($getEnvironmentValue('DB_SSL', 'false'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($dbSslEnabled === null) {
    throw new RuntimeException('DB_SSL must be set to true or false.');
}

$conn = mysqli_init();
if (!$conn) {
    throw new RuntimeException('Unable to initialize the database connection.');
}
if (!$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10)) {
    throw new RuntimeException('Unable to configure the database connection timeout.');
}
$dbSslCa = $getEnvironmentValue('DB_SSL_CA', '/etc/ssl/certs/ca-certificates.crt');
if ($dbSslEnabled) {
    if ($dbSslCa === '' || !$conn->ssl_set(null, null, $dbSslCa, null, null)) {
        throw new RuntimeException('Unable to configure the database CA certificate.');
    }
    if (!$conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true)) {
        throw new RuntimeException('Unable to enable database certificate verification.');
    }
}
$clientFlags = $dbSslEnabled ? MYSQLI_CLIENT_SSL : 0;
if (!$conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, null, $clientFlags)) {
    die("Connection failed: " . $conn->connect_error);
}
if (!$conn->set_charset('utf8mb4')) {
    throw new RuntimeException('Unable to set the database character set.');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'DatabaseSessionHandler.php';
$sessionHandler = new DatabaseSessionHandler($conn);
if (!session_set_save_handler($sessionHandler, true)) {
    throw new RuntimeException('Unable to configure persistent session storage.');
}

// Keep authenticated sessions protected and active for 30 minutes of inactivity.
$session_timeout = 1800;
$session_cookie_lifetime = 604800;
ini_set('session.gc_maxlifetime', (string) $session_timeout);
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