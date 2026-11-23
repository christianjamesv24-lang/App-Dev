<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$ssl = filter_var(getenv('DB_SSL') ?: 'false', FILTER_VALIDATE_BOOLEAN);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
if ($ssl) {
    $ca = getenv('DB_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt';
    $conn->ssl_set(null, null, $ca, null, null);
}
$connected = $conn->real_connect(
    getenv('DB_HOST') ?: '127.0.0.1',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'baketrack',
    (int) (getenv('DB_PORT') ?: 3306),
    null,
    $ssl ? MYSQLI_CLIENT_SSL : 0
);
if (!$connected) {
    throw new RuntimeException('Unable to connect to the database for migrations.');
}
$conn->set_charset('utf8mb4');

function hasColumn(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function hasIndex(mysqli $conn, string $table, string $index): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->bind_param('ss', $table, $index);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

$conn->begin_transaction();
try {
    if (!hasColumn($conn, 'users', 'is_active')) {
        $conn->query('ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1');
        fwrite(STDOUT, "Added users.is_active\n");
    }

    if (!hasColumn($conn, 'orders', 'idempotency_key')) {
        $conn->query('ALTER TABLE orders ADD COLUMN idempotency_key CHAR(64) NULL');
        fwrite(STDOUT, "Added orders.idempotency_key\n");
    }
    if (!hasIndex($conn, 'orders', 'idempotency_key')) {
        $conn->query('ALTER TABLE orders ADD UNIQUE KEY idempotency_key (idempotency_key)');
        fwrite(STDOUT, "Added orders.idempotency_key index\n");
    }
    if (!hasIndex($conn, 'orders', 'status_order_date')) {
        $conn->query('ALTER TABLE orders ADD KEY status_order_date (status, order_date, id)');
        fwrite(STDOUT, "Added orders.status_order_date index\n");
    }
    if (!hasIndex($conn, 'orders', 'user_status_order_date')) {
        $conn->query('ALTER TABLE orders ADD KEY user_status_order_date (user_id, status, order_date, id)');
        fwrite(STDOUT, "Added orders.user_status_order_date index\n");
    }
    if (!hasIndex($conn, 'transactions', 'transaction_date_id')) {
        $conn->query('ALTER TABLE transactions ADD KEY transaction_date_id (transaction_date, id)');
        fwrite(STDOUT, "Added transactions.transaction_date_id index\n");
    }

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    throw $error;
}

fwrite(STDOUT, "Database schema is up to date.\n");
