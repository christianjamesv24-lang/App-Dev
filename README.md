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

Assistant direct-sale reports record the walk-in customer's name along with
the server-calculated total, payment, and change. Existing databases gain the
optional `orders.customer_name` field automatically when an assistant opens
the direct-sales report.

New databases should be created from `database/schema.sql`. The Docker image
applies `scripts/migrate.php` automatically at startup, so earlier databases
gain active-account tracking, order idempotency, and the indexes used by the
paginated order and transaction lists without a manual step. For non-Docker
deployments, apply the equivalent `database/migrations/20261122_graybox_hardening.sql`
once. Deleting a user account now deactivates and anonymizes it while retaining
order and review history. Password creation and reset require at least eight
characters.

## Deploy a free Render demo

The root `render.yaml` defines a single Free Docker web service. It expects an
external MySQL-compatible TiDB Cloud Starter instance; it does not provision
paid Render services, a Render database, or a persistent disk. Keep the TiDB
spending limit at zero to stay within its free quota.

Render Free services sleep after 15 minutes without traffic and have ephemeral
filesystems. Uploaded product images are lost on restarts, sleeps, and
redeploys. Render Free also has no Shell access and blocks outbound SMTP ports.
Password-recovery email uses Brevo's HTTPS API; configure `BREVO_API_KEY` and
`MAIL_FROM` in Render after creating and verifying a sender in Brevo. Use this
setup only for a non-production demo, not live bakery records.

Create a separate TiDB database named `baketrack_demo` and apply
`database/schema.sql` to it. Do not import `database/baketrack.sql`, which
contains development data. When creating the Render Blueprint, set `DB_HOST`,
`DB_USER`, and `DB_PASS` from TiDB's connection details; the remaining
connection settings are preconfigured for TiDB TLS. Add the Brevo API key to
`BREVO_API_KEY` and set `MAIL_FROM` to a sender address verified in Brevo.
Keep the API key only in Render's environment settings.

To create the first administrator, run `php scripts/create-admin.php` from a
trusted local terminal with the TiDB connection variables available in the
environment. TiDB must allow that computer's IP temporarily for this step.
The script securely prompts for the account details and refuses to create
another administrator if one already exists. Never commit database or mail
credentials.

## Deploy on Vercel

**Plan restriction:** Vercel's Hobby terms limit use to personal or
non-commercial purposes. Do not deploy BakeTrack as a live bakery/business
system on Hobby. Use Hobby only if this is a non-commercial demo; a commercial
deployment requires a paid plan or another host.

The root Dockerfile runs Apache/PHP and the Vercel Blob SDK for product images.
Vercel's Docker container runtime is in beta, so confirm it is available to
your Vercel account before creating the project. The container listens on
`PORT` (default `80`), and product image uploads are limited to 4 MB to stay
within Vercel's request-size limit.

The app requires a separate MySQL-compatible database because Vercel does not
provide MySQL. TiDB Cloud Starter advertises a free quota for up to five
eligible instances per organization: 5 GiB row storage, 5 GiB columnar
storage, and 50 million request units per instance each month. Free instances
are throttled when their quota is exhausted. Review TiDB's current terms and
select a free instance, not a scalable instance, before creating one. Configure
a public TLS endpoint and apply `database/schema.sql` using TiDB's SQL editor.
The schema contains no development accounts or sample orders.

Set these Vercel environment variables for the project:

- `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, and `DB_NAME` from TiDB.
- `DB_SSL=true` to require TLS. The container uses its system CA bundle by
  default; set `DB_SSL_CA` only if TiDB supplies a different CA certificate
  path.
- `APP_URL` to the app's public HTTPS URL (recommended for password recovery).
- Either `MAIL_PROVIDER=smtp` with the `SMTP_*` settings, or
  `MAIL_PROVIDER=brevo` with `BREVO_API_KEY` and a verified `MAIL_FROM` sender.

Create a **public** Vercel Blob store and connect it to the project. The SDK
uses Vercel's short-lived OIDC credentials when available; if the container
runtime does not provide OIDC, add `BLOB_READ_WRITE_TOKEN` through Vercel's
environment settings and never commit it. Vercel Hobby currently includes
1 GB of Blob storage, 10,000 simple operations, 2,000 advanced operations, and
10 GB of Blob transfer per month. Hobby also has monthly compute allowances;
exceeding them can pause deployments. Product records store Blob URLs, and
deleting or replacing a product removes its stored image. Database-backed PHP
sessions survive container restarts and scaling.

After connecting the project and pulling its environment variables locally,
create the first admin with the existing secure prompt:

```powershell
vercel env pull .env
composer install
php scripts/create-admin.php
```

The script uses the pulled DB settings to create the account in TiDB. On
Windows, if TiDB requires a CA file not present in the local system bundle,
download the CA from TiDB and set `$env:DB_SSL_CA` to its local path before
running the script. It prompts for the admin password without echoing it. Do
not paste environment values or passwords into chat.

Install PHP dependencies before running the app:

```powershell
composer install
```

## Password recovery email

The **Forgot?** link sends a one-time password reset link through the selected
email provider. The link expires after one hour, and requesting another link
invalidates the previous one. The recovery form uses a generic response so it
does not reveal whether an email address has an account.

On Render Free, set `MAIL_PROVIDER=brevo` and configure `BREVO_API_KEY` and
`MAIL_FROM` in the Render environment. Create a Brevo account, verify a sender
address there, and use that address as `MAIL_FROM`. The app uses Brevo's HTTPS
API, so SMTP port restrictions do not apply. Do not send API keys in chat or
commit them to the repository.

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