<?php
require_once dirname(__DIR__) . '/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

$existingAdmins = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($existingAdmins->num_rows > 0) {
    fwrite(STDERR, "An administrator account already exists; no account was created.\n");
    exit(1);
}

$username = trim((string) readline('Admin username: '));
$fullname = trim((string) readline('Admin full name: '));
$email = trim((string) readline('Admin email: '));
if ($username === '' || $fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Enter a username, full name, and valid email address.\n");
    exit(1);
}

fwrite(STDOUT, "Admin password (at least 12 characters): ");
if (PHP_OS_FAMILY === 'Windows') {
    $password = shell_exec(
        'powershell -NoProfile -Command "$secure = Read-Host -AsSecureString; '
        . '$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure); '
        . 'try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) } '
        . 'finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }"'
    );
    if (!is_string($password)) {
        fwrite(STDERR, "Unable to securely read a password from this terminal.\n");
        exit(1);
    }
    $password = rtrim($password, "\r\n");
} else {
    $terminalState = trim((string) shell_exec('stty -g'));
    if ($terminalState === '') {
        fwrite(STDERR, "Unable to securely read a password from this terminal.\n");
        exit(1);
    }

    exec('stty -echo');
    try {
        $password = trim((string) fgets(STDIN));
    } finally {
        exec('stty ' . escapeshellarg($terminalState));
        fwrite(STDOUT, "\n");
    }
}

if (strlen($password) < 12) {
    fwrite(STDERR, "The password must contain at least 12 characters.\n");
    exit(1);
}

$userCheck = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
$userCheck->bind_param('ss', $username, $email);
$userCheck->execute();
if ($userCheck->get_result()->num_rows > 0) {
    fwrite(STDERR, "That username or email is already in use.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insert = $conn->prepare("INSERT INTO users (username, password, email, fullname, role) VALUES (?, ?, ?, ?, 'admin')");
$insert->bind_param('ssss', $username, $passwordHash, $email, $fullname);
if (!$insert->execute()) {
    fwrite(STDERR, "Unable to create the administrator account.\n");
    exit(1);
}

fwrite(STDOUT, "Administrator account created.\n");
