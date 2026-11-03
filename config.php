<?php
// Keep authenticated sessions protected and active for 30 minutes of inactivity.
$session_timeout = 1800;
$session_cookie_lifetime = 604800;
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => $session_cookie_lifetime,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'baketrack');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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