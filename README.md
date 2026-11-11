# BakeTrack

## Run locally

You do not need to start Laragon's web server for local development. From this
project folder, run:

```powershell
npm run serve
```

Then open:

<http://127.0.0.1:8000/>

You can also use `npm start` or `npm run dev`. Stop the server with `Ctrl+C`.

This uses PHP's built-in development server. Make sure `php` and `npm` are
available in your terminal.

Install PHP dependencies before running the app:

```powershell
composer install
```

## Password recovery email

The **Forgot?** link sends a one-time password reset link through Gmail SMTP.
The link expires after one hour, and requesting another link invalidates the
previous one. The recovery form uses a generic response so it does not reveal
whether an email address has an account.

To send mail from Gmail:

1. Enable 2-Step Verification on the Gmail account that will send recovery
   emails.
2. Create a Google App Password for this app. Use the App Password, not your
   regular Gmail password. Google only shows it when created.
3. Use the local setup script. It asks for the Gmail address and prompts for
   the App Password without displaying it. The password is saved to `.env`,
   which is ignored by Git. If `.env` already exists, confirm the update; the
   existing app URL is kept as the prompt default.

For first-time setup or to change the sending Gmail, from PowerShell in the
project folder:

```powershell
.\setup-gmail.ps1
composer install
npm run serve
```

The script asks for the app's base URL too. Leave the suggested
`http://127.0.0.1:8000` for testing on the same computer. To open reset links
on a phone, use an HTTPS address that the phone can reach. The app loads `.env`
automatically; restart the PHP server after setup.

The sender should be the Gmail account used for SMTP (or an alias configured
and verified in that Gmail account). Restart the PHP server after changing
these settings. The server must be able to reach Gmail on port 587. The reset
link also needs an `APP_URL` reachable by the device opening it; `127.0.0.1`
only works on the same computer, so use a public HTTPS URL for phones or
production.

For development only, SMTP can be pointed to a local mail catcher by setting
`SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE=none`, and `SMTP_AUTH=false`. Unencrypted
SMTP is deliberately restricted to loopback hosts; use Gmail TLS in production.
Recovery attempts are recorded in the PHP error log if mail delivery fails.
The `password_reset_tokens` table is included in `database/baketrack.sql` and
is also created on demand.