<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function readSecret(string $prompt): string
{
    fwrite(STDOUT, $prompt);

    if (PHP_OS_FAMILY === 'Windows') {
        $secret = shell_exec(
            'powershell -NoProfile -Command "$secure = Read-Host -AsSecureString; '
            . '$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure); '
            . 'try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) } '
            . 'finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }"'
        );
        if (!is_string($secret)) {
            throw new RuntimeException('Unable to securely read input from this terminal.');
        }
        return rtrim($secret, "\r\n");
    }

    $terminalState = trim((string) shell_exec('stty -g'));
    if ($terminalState === '') {
        throw new RuntimeException('Unable to securely read input from this terminal.');
    }

    exec('stty -echo');
    try {
        $secret = fgets(STDIN);
        if ($secret === false) {
            throw new RuntimeException('Unable to read input from this terminal.');
        }
        return rtrim($secret, "\r\n");
    } finally {
        exec('stty ' . escapeshellarg($terminalState));
        fwrite(STDOUT, PHP_EOL);
    }
}

function readNewPassword(string $label): string
{
    while (true) {
        $password = readSecret($label . ' password (at least 12 characters): ');
        $confirmation = readSecret('Confirm ' . strtolower($label) . ' password: ');
        if (strlen($password) < 12) {
            fwrite(STDERR, "Password must be at least 12 characters.\n");
            continue;
        }
        if (!hash_equals($password, $confirmation)) {
            fwrite(STDERR, "Passwords do not match; try again.\n");
            continue;
        }
        return $password;
    }
}

$host = trim((string) readline('TiDB host: '));
$username = trim((string) readline('TiDB database username: '));
$database = trim((string) readline('TiDB database name [baketrack_demo]: '));
$database = $database === '' ? 'baketrack_demo' : $database;
$portInput = trim((string) readline('TiDB port [4000]: '));
$port = $portInput === '' ? 4000 : filter_var($portInput, FILTER_VALIDATE_INT);
$caFile = trim((string) readline('TiDB CA certificate path (optional): '));

if ($host === '' || $username === '' || $port === false || $port < 1 || $port > 65535) {
    fwrite(STDERR, "Enter a valid TiDB host, database username, and port.\n");
    exit(1);
}

$databasePassword = readSecret('TiDB database password: ');
$adminPassword = readNewPassword('Admin');
$assistantPassword = readNewPassword('Assistant');

$conn = mysqli_init();
if (!$conn) {
    throw new RuntimeException('Unable to initialize the database connection.');
}
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
if ($caFile !== '' && !$conn->ssl_set(null, null, $caFile, null, null)) {
    throw new RuntimeException('Unable to configure the TiDB CA certificate.');
}
$conn->real_connect(
    $host,
    $username,
    $databasePassword,
    $database,
    $port,
    null,
    MYSQLI_CLIENT_SSL
);
$databasePassword = '';
$conn->set_charset('utf8mb4');

$accounts = [
    [
        'username' => 'admin',
        'password' => $adminPassword,
        'email' => 'admin@baketrack.local',
        'fullname' => 'Vencent Mariano',
        'role' => 'admin',
    ],
    [
        'username' => 'mark.ceghar',
        'password' => $assistantPassword,
        'email' => 'assistant.new@baketrack.local',
        'fullname' => 'Mark Anthony V. Ceghar',
        'role' => 'assistant',
    ],
];

foreach ($accounts as &$account) {
    $account['password_hash'] = password_hash($account['password'], PASSWORD_DEFAULT);
    if (!is_string($account['password_hash'])) {
        throw new RuntimeException('Unable to hash an account password.');
    }
    $account['password'] = '';
}
unset($account, $adminPassword, $assistantPassword);

$conn->begin_transaction();
try {
    $check = $conn->prepare(
        'SELECT id FROM users WHERE username IN (?, ?) OR email IN (?, ?) LIMIT 1'
    );
    $adminUsername = $accounts[0]['username'];
    $assistantUsername = $accounts[1]['username'];
    $adminEmail = $accounts[0]['email'];
    $assistantEmail = $accounts[1]['email'];
    $check->bind_param(
        'ssss',
        $adminUsername,
        $assistantUsername,
        $adminEmail,
        $assistantEmail
    );
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new RuntimeException(
            'A target username or email already exists in the destination; no accounts were migrated.'
        );
    }

    $insertUser = $conn->prepare(
        'INSERT INTO users (username, password, email, fullname, role) VALUES (?, ?, ?, ?, ?)'
    );
    $insertAdmin = $conn->prepare(
        'INSERT INTO admins (user_id, username, email, password, is_active) VALUES (?, ?, ?, ?, 1)'
    );

    foreach ($accounts as $account) {
        $passwordHash = $account['password_hash'];
        $insertUser->bind_param(
            'sssss',
            $account['username'],
            $passwordHash,
            $account['email'],
            $account['fullname'],
            $account['role']
        );
        $insertUser->execute();

        if ($account['role'] === 'admin') {
            $userId = $conn->insert_id;
            $insertAdmin->bind_param(
                'isss',
                $userId,
                $account['username'],
                $account['email'],
                $passwordHash
            );
            $insertAdmin->execute();
        }
    }

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    throw $error;
}

fwrite(STDOUT, "Migrated the admin and assistant accounts successfully.\n");
