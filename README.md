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

## Deploy on Render

The root `render.yaml` defines a PHP/Apache web service and a private MySQL 8.4
service in Singapore. Both services use paid plans and persistent disks so
database contents and product uploads survive restarts. Render charges for
these resources while they exist; review the current Render pricing before
creating the Blueprint.

The MySQL service initializes a clean schema from `database/schema.sql`.
Development accounts, password hashes, and sample orders from
`database/baketrack.sql` are intentionally not imported. After the Blueprint
is deployed, open the web service's Shell and run:

```sh
php scripts/create-admin.php
```

The script securely prompts for the first admin account and refuses to create
another if an admin already exists. Add products through the admin dashboard.
Set the `SMTP_*` environment variables in Render to enable account recovery
email; never commit `.env` or mail credentials.

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
- The `SMTP_*` settings if account recovery emails should be sent.

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