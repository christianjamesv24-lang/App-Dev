
<?php
/**
 * =========================================================
 * BAKE TRACK - MAIN CONFIGURATION
 * Database: baketrack
 * Database Driver: MySQLi
 * Character Set: utf8mb4
 * =========================================================
 */

// =========================================================
// SESSION CONFIGURATION
// =========================================================

$session_timeout = 1800; // 30 minutes inactivity
$session_cookie_lifetime = 604800; // 7 days

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => $session_cookie_lifetime,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Session inactivity timeout
if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $session_timeout
) {
    $_SESSION = [];

    session_destroy();

    session_start();
    session_regenerate_id(true);
}

$_SESSION['last_activity'] = time();


// =========================================================
// DATABASE CONFIGURATION
// =========================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'baketrack');
define('DB_CHARSET', 'utf8mb4');

// Enable MySQLi exceptions for database errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $conn = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME
    );

    // Set connection character encoding
    $conn->set_charset(DB_CHARSET);

} catch (mysqli_sql_exception $e) {

    // Keep technical database errors in the server log
    error_log(
        'BakeTrack Database Connection Error: ' .
        $e->getMessage()
    );

    // Show a generic error to visitors
    http_response_code(500);

    exit(
        'Unable to connect to the database. ' .
        'Please contact the system administrator.'
    );
}


// =========================================================
// APPLICATION COLOR PALETTE
// =========================================================

define('COLORS', [

    'primary' => '#8B6914',
    'secondary' => '#D4A574',
    'accent' => '#F5E6D3',
    'dark' => '#4A3520',
    'light' => '#FFF8F0',

    'success' => '#4CAF50',
    'danger' => '#E74C3C',
    'warning' => '#F39C12'

]);

?>
