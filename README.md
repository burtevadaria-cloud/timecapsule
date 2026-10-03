# TimeCapsule: pure PHP (object-oriented)

This is the TimeCapsule app written in plain PHP (8.2+) without a framework. It uses PDO for
PostgreSQL, plain PHP templates and small classes (one class per file, namespace `App\`).
The only library is [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv), which reads the
`.env` file. It is installed with [Composer](https://getcomposer.org), which also loads our
own classes (PSR-4 autoloading: `App\Foo\Bar` lives in `src/Foo/Bar.php`). The
[root README](../README.md) explains what the app does and lists the other variants.

```
public/index.php                 front controller (every request starts here)
public/css/app.css               the only stylesheet
public/js/app.js                 small browser script: date-time picker, local times
public/vendor/flatpickr/         flatpickr date-time picker (vendored, MIT license)
src/App.php                      creates and connects all objects, routes, error pages
src/Config.php                   settings from the environment / .env
src/Database.php                 PDO wrapper: query(), fetchOne(), fetchAll()
src/Router.php                   "GET /capsules/{id}" -> controller method
src/View.php                     renders views/ inside the layout; e(), icon(), time(), form helpers
src/Session.php                  PHP session + flash messages
src/Csrf.php                     CSRF token for all forms
src/Auth.php                     current user, login/logout, AUTH_ENABLED switch
src/Storage.php                  save / delete / stream uploaded files (local disk)
src/Mailer.php                   simulated e-mail -> notifications table
src/CapsuleOpener.php            opens due capsules at the start of each request
src/Models/                      User, Capsule (a database row + its rules)
src/Repositories/                all SQL, one class per table
src/Validation/                  form validation (new capsule, sign up)
src/Support/Format.php           date-time, countdown and text formatting for the views
src/Http/                        HttpException (403/404 page), RedirectException
src/Controllers/                 one class per page group
views/                           HTML templates (layout.php + one file per page)
bin/init-db.php                  creates the tables from database/schema.sql
storage/uploads/                 uploaded files
storage/sessions/                PHP session files
deploy/nginx.conf                sample nginx site for an Ubuntu server
composer.json / composer.lock    Composer packages (vendor/ is not in git)
```

How a request flows: `public/index.php` -> `App::run()` -> `CapsuleOpener` opens due capsules
-> `Router` finds the controller method -> the controller uses repositories / services ->
`View` renders a template. Errors
are thrown as exceptions (`HttpException`, `RedirectException`) and turned into responses in
`App::run()`.

## Dates, times and time zones

The "Open at" field on the New capsule form takes a date **and** a time. The browser sends your
local time; the app stores UTC and shows times in the visitor's time zone:

- `public/js/app.js` turns the field into a calendar with a time picker
  ([flatpickr](https://flatpickr.js.org), vendored in `public/vendor/flatpickr`, MIT license).
  When the form is sent, it also fills the hidden field `open_at_utc` with the same moment in
  UTC (for example `2027-01-01T12:30:00.000Z`).
- `CapsuleValidator` uses `open_at_utc`. Without JavaScript only `open_at`
  (`2027-01-01T13:30`) arrives; the server cannot know your time zone then and treats that
  value as UTC. Seconds are dropped. The time must be in the future.
- `capsules.open_at` is a `TIMESTAMP` in UTC. Pages print every date with `$view->time(...)`:
  `<time data-local datetime="2027-01-01T12:30:00Z">1 Jan 2027, 12:30 UTC</time>`. The script
  replaces the text with the visitor's local time; without JavaScript the UTC text stays.
- The countdown shows minutes, hours or days ("Opens in 5 minutes", "Opens in 3 hours",
  "Opens in 106 days"), and "Opening soon…" when the time has passed but the capsule is not
  opened yet.

## Requirements

- **With Docker:** Docker Desktop (or Docker Engine) with Docker Compose v2. Nothing else.
- **Without Docker:** PHP 8.2+ ([XAMPP](https://www.apachefriends.org) works) with the extensions
  `pdo_pgsql`, `mbstring`, `fileinfo`, [Composer](https://getcomposer.org/download/) and a
  PostgreSQL database you can connect to.

Windows: see [Setting up a Windows computer](../README.md#setting-up-a-windows-computer).

## Run without Docker

1. Install the Composer packages and create the settings file:

   ```bash
   composer install
   cp .env.example .env
   ```

2. Open `.env` and enter your database: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.

3. Create the tables and start the app:

   ```bash
   php bin/init-db.php
   php -S localhost:8080 -t public
   ```

4. Open http://localhost:8080 and create an account.

The built-in server (`php -S`) is for development only. On a real server, use nginx + PHP-FPM
(see below).

## Run with Docker

Start Docker Desktop first and wait until it says "Engine running".

```bash
docker compose up --build
```

Open http://localhost:8080. The image runs `composer install --no-dev` while it is built, so
you do not need Composer on your machine. On startup the container waits for PostgreSQL, runs
`php bin/init-db.php`, and then starts Apache.

**Adminer** (a web UI for the database) starts together with the app at http://localhost:8081.
Log in with System `PostgreSQL`, Server `db`, Username `timecapsule`, Password `secret`,
Database `timecapsule`. Change its port with `ADMINER_HOST_PORT`.

Useful commands:

```bash
docker compose exec db psql -U timecapsule             # SQL shell
docker compose logs -f app                             # Apache logs
docker compose down -v                                 # stop and delete all data
```

Guest mode (no login) and other host ports:

```
AUTH_ENABLED=false docker compose up -d                                    # macOS / Linux
$env:AUTH_ENABLED="false"; docker compose up -d                            # Windows PowerShell

APP_HOST_PORT=9080 DB_HOST_PORT=5433 docker compose up -d                  # macOS / Linux
$env:APP_HOST_PORT="9080"; $env:DB_HOST_PORT="5433"; docker compose up -d  # Windows PowerShell
```

In PowerShell the variables stay set until you close the window.

Uploaded files are stored in the `uploads` volume, mounted at `/var/www/html/storage/uploads`.
Database data is stored in the `db-data` volume.

## Configuration

All settings are environment variables. Without Docker they come from `.env`, which is
optional and is loaded by phpdotenv (`Dotenv::createImmutable(...)->safeLoad()`).
**Real environment variables always win over `.env`.** In Docker they are set in
`docker-compose.yml`. The code reads them through `App\Config`.

| Variable | Default | Meaning |
|---|---|---|
| `APP_PORT` | `8080` | Documentation only. PHP does not listen on a port itself. |
| `APP_URL` | `http://localhost:8080` | Public URL of the app |
| `APP_DEBUG` | `false` | `true` shows the error message on the 500 page |
| `DB_HOST` | `localhost` | PostgreSQL host |
| `DB_PORT` | `5432` | |
| `DB_NAME` | `timecapsule` | |
| `DB_USER` | `timecapsule` | |
| `DB_PASSWORD` | `secret` | |
| `UPLOAD_DIR` | `storage/uploads` | Upload folder. A relative path starts at the project root. |
| `MAX_UPLOAD_MB` | `5` | Maximum attachment size, in MB |
| `MAIL_DELAY_SECONDS` | `3` | How long the simulated "send e-mail" step takes |
| `AUTH_ENABLED` | `true` | `false`: no login, everybody is the built-in **Guest** user |

PHP's own upload limits must be a bit larger than `MAX_UPLOAD_MB`, so that the app can show its
own error message. Use `upload_max_filesize = 6M` and `post_max_size = 8M` (on Windows in
`C:\php\php.ini`). The Docker image
already sets these values in `docker/php.ini`.

## How capsules open

When a capsule's open time has passed, the next page request opens it and adds an "is now
open" notification. No cron job or background worker is needed.

`App::run()` calls `CapsuleOpener::openDue()` at the start of every request (except
`GET /health` and static files). It runs one `UPDATE ... RETURNING` statement, so two requests
at the same time never open the same capsule twice.

Quick test: move the open time into the past, then reload the page:

```sql
UPDATE capsules SET open_at = NOW() - interval '1 minute';
```

With Docker:

```bash
docker compose exec db psql -U timecapsule -c "UPDATE capsules SET open_at = NOW() - interval '1 minute'"
```

## Deploying to an Ubuntu server

These steps are for Ubuntu 24.04. They put nginx, PHP-FPM and PostgreSQL on one server.
All commands in this section run on the Linux server (for example over SSH from PowerShell:
`ssh ubuntu@<server-ip>`), not on your Windows computer.

**1. Install packages**

```bash
sudo apt update
sudo apt install -y nginx composer php8.3-cli php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-xml unzip postgresql git
```

(`fileinfo` is already included in the PHP 8.3 packages. `unzip` lets Composer unpack the
packages it downloads.)

**2. Create the database and user**

```bash
sudo -u postgres psql -c "CREATE USER timecapsule WITH PASSWORD 'secret';"
sudo -u postgres psql -c "CREATE DATABASE timecapsule OWNER timecapsule;"
```

**3. Copy the app and configure it**

```bash
sudo mkdir -p /var/www/timecapsule
sudo chown ubuntu:ubuntu /var/www/timecapsule
git clone <your-repo-url> /tmp/timecapsule-src
cp -r /tmp/timecapsule-src/open_capsules_php_oop/. /var/www/timecapsule/
cd /var/www/timecapsule
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env                    # set DB_PASSWORD, APP_URL, ...
php bin/init-db.php
```

PHP-FPM does not pass system environment variables to PHP (`clear_env = yes`), so on the server
the settings come from `.env`.

**4. File permissions**

PHP-FPM runs as `www-data` and must be able to write uploads and sessions:

```bash
sudo chown -R www-data:www-data /var/www/timecapsule/storage
sudo chmod -R 775 /var/www/timecapsule/storage
```

**5. PHP upload limits**

In `/etc/php/8.3/fpm/php.ini`, set:

```ini
upload_max_filesize = 6M
post_max_size = 8M
```

Then restart PHP-FPM: `sudo systemctl restart php8.3-fpm`.

**6. nginx**

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/timecapsule
sudo ln -s /etc/nginx/sites-available/timecapsule /etc/nginx/sites-enabled/timecapsule
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

The site's document root is `/var/www/timecapsule/public`, so `src/`, `storage/` and `.env` are
not reachable from the web.

**7. Firewall and check**

Allow HTTP (port 80) from everywhere and SSH (port 22) only from your own IP, for example
with `ufw`:

```bash
sudo ufw allow from <your-ip> to any port 22
sudo ufw allow 80/tcp
sudo ufw enable
```

Then open `http://<server-ip>/` and `http://<server-ip>/health`.

**Updating the app later:** copy the new files, run `composer install --no-dev
--optimize-autoloader` again (it also regenerates the class map for new classes in `src/`),
and restart PHP-FPM: `sudo systemctl restart php8.3-fpm`.
