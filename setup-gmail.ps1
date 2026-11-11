$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$envPath = Join-Path $projectRoot '.env'
$existingAppUrl = 'http://127.0.0.1:8000'

if (Test-Path -LiteralPath $envPath) {
    $confirmation = (Read-Host 'A .env file exists. Update its Gmail sender and App Password? (y/N)').Trim()
    if ($confirmation -notin @('y', 'Y', 'yes', 'YES', 'Yes')) {
        Write-Host 'No changes were made.'
        exit 0
    }

    foreach ($line in Get-Content -LiteralPath $envPath) {
        if ($line -match '^\s*APP_URL\s*=\s*(.*?)\s*$') {
            $existingAppUrl = $matches[1].Trim().Trim('"').Trim("'")
            break
        }
    }
}

$sender = (Read-Host 'Gmail address used to send password reset emails').Trim()
try {
    $parsedSender = [System.Net.Mail.MailAddress]::new($sender)
    if ($parsedSender.Address -ne $sender) {
        throw 'Enter a plain Gmail email address.'
    }
} catch {
    throw 'The Gmail sender address is invalid.'
}

$appUrl = (Read-Host "App URL users can open [$existingAppUrl]").Trim()
if ($appUrl -eq '') {
    $appUrl = $existingAppUrl
}

[uri]$parsedAppUrl = $null
if (
    -not [uri]::TryCreate($appUrl, [System.UriKind]::Absolute, [ref]$parsedAppUrl) -or
    $parsedAppUrl.Scheme -notin @('http', 'https') -or
    $parsedAppUrl.UserInfo -ne '' -or
    $parsedAppUrl.Query -ne '' -or
    $parsedAppUrl.Fragment -ne ''
) {
    throw 'APP_URL must be an absolute http:// or https:// base URL without a query or fragment.'
}

$securePassword = Read-Host 'Google App Password (not your regular Gmail password)' -AsSecureString
$passwordPointer = [IntPtr]::Zero
try {
    $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    $password = [regex]::Replace($password, '\s', '')
} finally {
    if ($passwordPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    }
}

if ($password -notmatch '\A[A-Za-z0-9]{16}\z') {
    $password = $null
    throw 'Google App Passwords are 16 letters. Re-run the script and enter the 16-character App Password.'
}

$contents = @(
    "APP_URL=$appUrl"
    'SMTP_HOST=smtp.gmail.com'
    'SMTP_PORT=587'
    'SMTP_SECURE=tls'
    'SMTP_AUTH=true'
    "SMTP_USERNAME=$sender"
    "SMTP_PASSWORD=$password"
    "MAIL_FROM=$sender"
) -join "`n"

[System.IO.File]::WriteAllText(
    $envPath,
    $contents + "`n",
    [System.Text.UTF8Encoding]::new($false)
)
$password = $null
$contents = $null

Write-Host "Gmail SMTP settings saved to the Git-ignored .env file."
Write-Host 'Restart the PHP server, then request a reset for an email address registered in BakeTrack.'
